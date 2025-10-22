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
        \Carbon\Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'es');
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
            // mismo número que usa el SP para la resolución subgerencial
            $numeroResolucion = $this->repository->numeroResolucionSubgerencial((int)$payload['codigo_resolucion']);

            // fecha/lugar desde BD si existen, fallback a hoy/Nuevo Chimbote
            $fechaResolucion = $res->fecha ? Carbon::parse($res->fecha) : Carbon::today();
            $lugarEmision    = $res->lugar_emision ?: 'Nuevo Chimbote';

            // Administrado: nombre/razón + identificador
            [$nombreAdmin, $identificador] = $this->resolverAdministrado($expediente);

            // VISTO (documentos en orden por fecha DESC con meses en español)
            $visto = $this->formatearVisto($documentos);

            // 1er artículo (solo el fragmento inicial que irá antes de ", en atención...")
            $textoResolucion = $this->textoResolucionInicial($tipoId, $nombreAdmin, $identificador);

            $numeroExpediente = (string) ($expediente->codigo_expediente ?? '');

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

            // Placeholders esperados en el .docx (TemplateProcessor usa ${...}):
            // ${numero_resolucion}, ${fecha_resolucion}, ${lugar_emision}
            // ${documentos_visto}
            // ${textoResolucion}, ${numeroExpediente}, ${administrado}

            $tp->setValue('numero_resolucion', $numeroResolucion);
            $tp->setValue('fecha_resolucion', $this->fechaLarga($fechaResolucion));
            $tp->setValue('lugar_emision', $lugarEmision);
            $tp->setValue('documentos_visto', $visto);

            // SE RESUELVE: variables pedidas
            $tp->setValue('textoResolucion', $textoResolucion);
            $tp->setValue('numeroExpediente', $numeroExpediente);
            $tp->setValue('administrado', $nombreAdmin);

            // Nombre de archivo basado en numero_resolucion
            $baseName = Str::of($numeroResolucion)
                ->replace(['/', ' '], ['-', '-'])
                ->upper()
                ->value();
            $fileName = "{$baseName}.docx";

            // Guardar en public/resoluciones para descarga
            $tmpPath = storage_path("app/tmp-{$fileName}");
            $tp->saveAs($tmpPath);

            $publicRelPath = 'resoluciones/' . $fileName; // disk public
            Storage::disk('public')->put($publicRelPath, file_get_contents($tmpPath));
            @unlink($tmpPath);

            // URL pública (requiere storage:link)
            $fileUrl = Storage::url($publicRelPath);

            // Adjuntar URL al modelo (no persistente) para que el Resource la exponga
            $res->setAttribute('urlResolucion', $fileUrl);

            return $res;
        });
    }

    // ----------------- Helpers -----------------

    private function resolverAdministrado(Expediente $expediente): array
    {
        $adm = $expediente->administrado;
        if (!$adm) return ['', ''];

        if ($adm->tipo === 'juridica' && $adm->razon_social) {
            $nombre = Str::of($adm->razon_social)->upper()->value();
            $ident  = $adm->ruc ? ('RUC N° ' . $adm->ruc) : '';
        } else {
            $nombre = Str::of(trim(($adm->nombres ?? '') . ' ' . ($adm->apellidos ?? '')))
            ->upper()
            ->value();
            $ident  = $adm->dni ? ('DNI N° ' . $adm->dni) : '';
        }
        return [$nombre, $ident];
    }

    private function formatearVisto(array $documentos): string
    {
        if (empty($documentos)) return '';

        // ordenar DESC (más reciente primero)
        usort($documentos, function ($a, $b) {
            return strcmp($b['fecha_doc'], $a['fecha_doc']);
        });

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
        // “05 de octubre del 2025” (con meses y días en español)
        // isoFormat SÍ respeta [literales] y usa tokens tipo Moment.js
        return $date->locale('es')->isoFormat('DD [de] MMMM [del] YYYY');
    }


    /**
     * Texto inicial del Artículo Primero según tipo.
     * 1 = No ha lugar, 2 = Sanción (ajusta a tu catálogo real).
     */
    private function textoResolucionInicial(int $tipoId, string $nombreAdmin, string $identificador): string
    {
        if ($tipoId === 1) {
            return "DECLARAR NO HA LUGAR AL INICIO DEL PROCEDIMIENTO ADMINISTRATIVO SANCIONADOR al administrado {$nombreAdmin}, identificado con {$identificador}";
        }
        if ($tipoId === 2) {
            return "IMPONER SANCIÓN a {$nombreAdmin}, identificado con {$identificador}";
        }
        return "DISPONER lo pertinente respecto del administrado {$nombreAdmin}, identificado con {$identificador}";
    }
}
