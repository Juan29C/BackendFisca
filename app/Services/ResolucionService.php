<?php

namespace App\Services;

use App\Models\Expediente;
use App\Repositories\ResolucionRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Resolucion;
use App\Models\TipoResolucion;
use App\Models\TiposDocumento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use Illuminate\Support\Str;

class ResolucionService
{
    protected ResolucionRepository $repository;

    public function __construct(ResolucionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?Resolucion
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Resolucion
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?Resolucion
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Crear Resolución vía SP y generar DOCX desde plantilla elegida.
     */
    public function crearDesdeSpFromRequest(int $idExpediente, array $payload, string $templateKey): Resolucion
    {
        return DB::transaction(function () use ($idExpediente, $payload, $templateKey) {
            $expediente = Expediente::with('administrado')->find($idExpediente);
            if (!$expediente) {
                throw new \DomainException('Expediente no encontrado');
            }

            $tipoId = (int) ($payload['id_tipo_resolucion'] ?? 0);
            $tipo = TipoResolucion::find($tipoId);
            if (!$tipo) {
                throw new \DomainException('Tipo de resolución no encontrado');
            }

            // --- validar documentos del payload ---
            $documentos = array_values(array_filter($payload['documentos'] ?? [], function ($d) {
                return isset($d['codigo_doc'], $d['fecha_doc'], $d['id_tipo'])
                    && $d['codigo_doc'] !== ''
                    && $d['fecha_doc']  !== ''
                    && !empty($d['id_tipo']);
            }));

            if (!empty($documentos)) {
                $idsPorIndice = [];
                foreach ($documentos as $idx => $doc) {
                    if (isset($doc['id_tipo'])) {
                        $idsPorIndice[$idx] = (int) $doc['id_tipo'];
                    }
                }
                $idsTipo    = array_values(array_unique(array_values($idsPorIndice)));
                $idsValidos = TiposDocumento::whereIn('id', $idsTipo)->pluck('id')->all();
                $idsInvalidos = array_values(array_diff($idsTipo, $idsValidos));

                if (!empty($idsInvalidos)) {
                    $errors = [];
                    foreach ($idsPorIndice as $idx => $idTipoEnviado) {
                        if (in_array($idTipoEnviado, $idsInvalidos, true)) {
                            $errors["documentos.$idx.id_tipo"] = [
                                "El tipo de documento (id={$idTipoEnviado}) no existe."
                            ];
                        }
                    }
                    $errors['documentos'] = [
                        'Tipos de documento inexistentes: ' . implode(', ', $idsInvalidos)
                    ];
                    throw ValidationException::withMessages($errors);
                }
            }

            // --- crear vía SP (esto guarda en BD) ---
            $res = $this->repository->createViaStoredProcedure(
                $idExpediente,
                (int) $payload['codigo_resolucion'],
                $tipoId,
                $documentos
            );
            if (!$res) {
                throw new RuntimeException('No se pudo recuperar la resolución creada.');
            }

            // --- preparar variables para la plantilla ---
            // numero_resolucion calculado de la misma forma que el SP:
            $numeroResolucion = $this->repository->numeroResolucionSubgerencial((int)$payload['codigo_resolucion']);

            // fecha/lugar desde BD si existen, fallback a hoy/Nuevo Chimbote
            $fechaResolucion = $res->fecha ? Carbon::parse($res->fecha) : Carbon::today();
            $lugarEmision    = $res->lugar_emision ?: 'Nuevo Chimbote';

            // Administrado: nombre/razón + identificador
            [$nombreAdmin, $identificador] = $this->resolverAdministrado($expediente);

            // VISTO (documentos en orden por fecha con long-date en español)
            $visto = $this->formatearVisto($documentos);

            // Artículos dinámicos según tipo de resolución
            [$art1, $art2, $art3] = $this->armarArticulos($tipoId, $expediente, $nombreAdmin, $identificador);

            // --- generar DOCX desde plantilla ---
            $relativeTemplate = config('templates.' . $templateKey); // p.ej. 'resolucion_no_ha_lugar.docx'
            if (!$relativeTemplate) {
                throw new \InvalidArgumentException('Plantilla no registrada.');
            }
            $templatePath = Storage::disk('templates')->path($relativeTemplate);
            if (!is_file($templatePath)) {
                throw new \RuntimeException("Plantilla no encontrada: {$templatePath}");
            }

            $tp = new TemplateProcessor($templatePath);

            // Placeholders esperados en el .docx:
            // {{numero_resolucion}}, {{fecha_resolucion}}, {{lugar_emision}}
            // {{documentos_visto}}
            // {{articulo_primero}}, {{articulo_segundo}}, {{articulo_tercero}}

            $tp->setValue('numero_resolucion', $numeroResolucion);
            $tp->setValue('fecha_resolucion', $this->fechaLarga($fechaResolucion)); // ej: "21 de octubre del 2025"
            $tp->setValue('lugar_emision', $lugarEmision);
            $tp->setValue('documentos_visto', $visto);
            $tp->setValue('articulo_primero', $art1);
            $tp->setValue('articulo_segundo', $art2);
            $tp->setValue('articulo_tercero', $art3);

            // Nombre de archivo basado en numero_resolucion
            $safeName = Str::of($numeroResolucion)->replace(['/', ' '], ['-', '-'])->value();
            $fileName = "{$safeName}.docx";

            // Guardar en public/resoluciones para descarga
            $tmpPath = storage_path("app/tmp-{$fileName}");
            $tp->saveAs($tmpPath);

            $publicRelPath = 'resoluciones/' . $fileName; // public disk
            Storage::disk('public')->put($publicRelPath, file_get_contents($tmpPath));
            @unlink($tmpPath);

            // URL pública (requiere storage:link)
            $fileUrl = Storage::url($publicRelPath);

            // Adjunta el URL como atributo runtime para que el Resource pueda retornarlo
            $res->setAttribute('file_url', $fileUrl);

            return $res;
        });
    }

    // ----------------- Helpers -----------------

    private function resolverAdministrado(Expediente $expediente): array
    {
        $adm = $expediente->administrado;
        if (!$adm) return ['', ''];

        if ($adm->tipo === 'juridica' && $adm->razon_social) {
            $nombre = $adm->razon_social;
            $ident  = $adm->ruc ? ('RUC N° ' . $adm->ruc) : '';
        } else {
            $nombre = trim(($adm->nombres ?? '') . ' ' . ($adm->apellidos ?? ''));
            $ident  = $adm->dni ? ('DNI N° ' . $adm->dni) : '';
        }
        return [$nombre, $ident];
    }

    private function formatearVisto(array $documentos): string
    {
        if (empty($documentos)) return '';

        // ordenar por fecha_doc ASC
        usort($documentos, fn($a, $b) => strcmp($a['fecha_doc'], $b['fecha_doc']));

        $parts = [];
        foreach ($documentos as $d) {
            $fecha = Carbon::parse($d['fecha_doc']);
            $parts[] = "{$d['codigo_doc']} de fecha " . $this->fechaLarga($fecha);
        }
        // Unir con '; ' y cerrar con ';'
        return implode('; ', $parts) . ';';
    }

    private function fechaLarga(Carbon $date): string
    {
        // "31 de julio del 2025"
        // Nota: translatedFormat requiere setLocale('es')
        return $date->translatedFormat('d \\d\\e F \\d\\e\\l Y');
    }

    /**
     * Construye textos de artículos según tipo de resolución.
     * 1 = No ha lugar, 2 = Continuar / Imponer sanción (ajusta a tu catálogo real).
     */
    private function armarArticulos(int $tipoId, Expediente $expediente, string $nombreAdmin, string $identificador): array
    {
        $codigoExp = $expediente->codigo_expediente ?? '';
        if ($tipoId === 1) {
            $art1 = "DECLARAR NO HA LUGAR AL INICIO DEL PROCEDIMIENTO ADMINISTRATIVO SANCIONADOR al administrado {$nombreAdmin}, identificado con {$identificador}, en atención a que los hechos que meritaron la elaboración del acta de constatación e imputación de cargo no tienen la condición de infracción administrativa, considerando el Expediente Administrativo N° {$codigoExp}.";
        } elseif ($tipoId === 2) {
            $art1 = "IMPONER SANCIÓN a {$nombreAdmin}, identificado con {$identificador}, conforme a las consideraciones expuestas en la parte motiva de la presente resolución.";
        } else {
            $art1 = "DISPONER lo pertinente respecto del administrado {$nombreAdmin}, identificado con {$identificador}, de acuerdo con los considerandos de la presente resolución.";
        }

        $art2 = "RECOMENDAR al administrado {$nombreAdmin} a monitorear constantemente sus documentos municipales;";
        $art3 = "NOTIFÍQUESE al administrado {$nombreAdmin};";

        return [$art1, $art2, $art3];
    }
}
