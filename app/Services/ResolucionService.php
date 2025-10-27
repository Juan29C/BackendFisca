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

            // --- validar documentos del payload (como ya lo tienes) ---
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
                $idsTipo     = array_values(array_unique(array_values($idsPorIndice)));
                $idsValidos  = TiposDocumento::whereIn('id', $idsTipo)->pluck('id')->all();
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

            // Fallback: si tu SP aún no persiste fecha_doc, actualiza aquí:
            if (!empty($documentos)) {
                foreach ($documentos as $d) {
                    DB::table('documento')
                        ->where('id_expediente', $idExpediente)
                        ->where('id_tipo', (int)$d['id_tipo'])
                        ->where('codigo_doc', $d['codigo_doc'])
                        ->whereNull('fecha_doc')
                        ->update(['fecha_doc' => $d['fecha_doc']]); // 'Y-m-d'
                }
            }

            // Cargar relaciones para resource y para VISTO
            $res->load(['tipoResolucion', 'documentos.tipoDocumento']);

            // --- preparar variables para la plantilla ---
            $numeroResolucion = $this->repository->numeroResolucionSubgerencial((int)$payload['codigo_resolucion']);
            $fechaResolucion  = $res->fecha ? Carbon::parse($res->fecha) : Carbon::today();
            $lugarEmision     = $res->lugar_emision ?: 'Nuevo Chimbote';
            [$nombreAdmin, $identificador] = $this->resolverAdministrado($expediente);

            // VISTO desde modelos (incluye tipo y fecha formateada)
            $visto = $this->formatearVistoFromModels($res->documentos);

            $textoResolucion  = $this->textoResolucionInicial($tipoId, $nombreAdmin, $identificador);
            $numeroExpediente = (string) ($expediente->codigo_expediente ?? '');

            // --- generar DOCX desde plantilla ---
            $relativeTemplate = config('templates.' . $templateKey);
            if (!$relativeTemplate) {
                throw new \InvalidArgumentException('Plantilla no registrada.');
            }
            $templatePath = Storage::disk('templates')->path($relativeTemplate);
            if (!is_file($templatePath)) {
                throw new \RuntimeException("Plantilla no encontrada: {$templatePath}");
            }

            $tp = new TemplateProcessor($templatePath);
            $tp->setValue('numero_resolucion', $numeroResolucion);
            $tp->setValue('fecha_resolucion', $this->fechaLarga($fechaResolucion));
            $tp->setValue('lugar_emision', $lugarEmision);
            $tp->setValue('documentos_visto', $visto);
            $tp->setValue('textoResolucion', $textoResolucion);
            $tp->setValue('numeroExpediente', $numeroExpediente);
            $tp->setValue('administrado', $nombreAdmin);

            $baseName = Str::of($numeroResolucion)->replace(['/', ' '], ['-', '-'])->upper()->value();
            $fileName = "{$baseName}.docx";

            $tmpPath = storage_path("app/tmp-{$fileName}");
            $tp->saveAs($tmpPath);

            $publicRelPath = 'resoluciones/' . $fileName; // disk public
            Storage::disk('public')->put($publicRelPath, file_get_contents($tmpPath));
            @unlink($tmpPath);

            $fileUrl = Storage::url($publicRelPath);
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

    private function formatearVistoFromModels($docs): string
    {
        if (!$docs || $docs->isEmpty()) return '';

        // Ordenar DESC por fecha (nulos al final)
        $sorted = $docs->sortByDesc(function ($d) {
            return $d->fecha_doc ? $d->fecha_doc->format('Y-m-d') : '0000-00-00';
        });

        $parts = [];
        foreach ($sorted as $d) {
            $tipo   = optional($d->tipoDocumento)->descripcion ?: 'Documento';
            $codigo = $d->codigo_doc ?? '';
            $hasN   = preg_match('/\bN[°º\.]?\b/i', $codigo);
            $pref   = $hasN ? '' : 'N° ';
            $fecha  = $d->fecha_doc
                ? $this->fechaLarga(\Carbon\Carbon::parse($d->fecha_doc))
                : 's/f';

            $parts[] = "{$tipo} {$pref}{$codigo} de fecha {$fecha}";
        }

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
