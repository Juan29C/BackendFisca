<?php

namespace App\Services;

use App\Enums\EstadoExpedienteEnum as EE;
use App\Exceptions\DocumentoDuplicadoException;
use App\Exceptions\EstadoExpedienteInvalidoException;
use App\Models\Documento;
use App\Models\Expediente;
use App\Repositories\DocumentoRepository;
use App\Repositories\ResolucionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentoService
{
    public function __construct(
        private ResolucionRepository $repo,
        private WordService $word,
        private DocumentoRepository $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?Documento
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Documento
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?Documento
    {
        return $this->repository->update($id, $data);
    }

    public function updateDocumento(int $id, array $data): Documento
    {
        DB::beginTransaction();
        try {
            $documento = $this->repository->find($id);
            if (!$documento) {
                throw new \Exception("Documento no encontrado");
            }

            // Si viene un nuevo archivo, reemplazarlo
            if (isset($data['file']) && $data['file']->isValid()) {
                if ($documento->ruta && Storage::disk('public')->exists($documento->ruta)) {
                    Storage::disk('public')->delete($documento->ruta);
                }

                $baseFolder = dirname($documento->ruta);
                $filename   = Str::random(40) . '.pdf';
                $storedPath = Storage::disk('public')->putFileAs($baseFolder, $data['file'], $filename);
                $data['ruta'] = $storedPath;
            }

            unset($data['file']);
            $this->repository->update($id, $data);

            DB::commit();
            return $this->repository->find($id)->fresh(['tipoDocumento']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }


    // ✅ Eliminar documento (y borrar archivo físico)
    public function deleteDocumento(int $id): bool
    {
        $documento = $this->repository->find($id);
        if (!$documento) {
            throw new \Exception("Documento no encontrado");
        }

        // Eliminar archivo físico si existe
        if ($documento->ruta && Storage::disk('public')->exists($documento->ruta)) {
            Storage::disk('public')->delete($documento->ruta);
        }

        return $this->repository->delete($id);
    }

    public function generar(string $templateKey, array $payload): string
    {
        $map = config('templates');
        if (!isset($map[$templateKey])) {
            throw new \InvalidArgumentException('Plantilla no registrada.');
        }
        $templatePath = $map[$templateKey];

        $vars = [];

        if (!empty($payload['codigo_titulo'])) {
            $vars['titulo'] = $this->repo->numeroResolucionSimple((int)$payload['codigo_titulo']);
        } elseif (!empty($payload['titulo'])) {
            $vars['titulo'] = (string)$payload['titulo'];
        }

        if (!empty($payload['descripcion'])) {
            $vars['descripcion'] = (string)$payload['descripcion'];
        } elseif (!empty($payload['id_visto'])) {
            $vars['descripcion'] = $this->repo->descripcionVisto((int)$payload['id_visto']) ?? '';
        }

        if (!empty($payload['fecha_emision'])) {
            $vars['fecha_emision'] = (string)$payload['fecha_emision'];
        }

        $options = [];

        return $this->word->fromTemplate($templatePath, $vars, $options);
    }

    public function uploadSingle(Expediente $expediente, array $data): Documento
    {
        // 1) Validaciones de estado por tipo
        $this->assertEstadoValidoParaTipo($expediente, (int) $data['id_tipo']);

        // 2) Variables base
        $tipoId    = (int) $data['id_tipo'];
        $codigoDoc = $data['codigo_doc'] ?? null;

        DB::beginTransaction();
        $storedPath = null;

        try {
            // 3) Si ya existe con archivo → duplicado
            if ($this->repository->existsWithFileForDoc($expediente->id, $tipoId, $codigoDoc)) {
                throw new DocumentoDuplicadoException();
            }

            // 4) Reutilizar un borrador (sin archivo) si existe
            $documento = $this->repository->findDraftDoc($expediente->id, $tipoId, $codigoDoc);

            // 5) Preparar ruta de guardado
            $adm         = $expediente->administrado;
            $slugPersona = $adm?->ruc ?: $adm?->dni ?: ('expediente_' . $expediente->id);
            $baseFolder  = "expedientes/{$slugPersona}";
            $filename    = Str::random(40) . '.pdf';

            // 6) Subir archivo
            $storedPath = Storage::disk('public')->putFileAs($baseFolder, $data['file'], $filename);

            // 7) Crear o actualizar el registro “borrador”
            if ($documento) {
                // Actualiza el borrador existente
                $documento->fill([
                    'fecha_doc'   => $data['fecha_doc'] ?? $documento->fecha_doc,
                    'descripcion' => $data['descripcion'] ?? $documento->descripcion,
                    'ruta'        => $storedPath,
                ])->save();
            } else {
                // Crea uno nuevo (no existía nada)
                $documento = $this->repository->create([
                    'id_expediente' => $expediente->id,
                    'id_tipo'       => $tipoId,
                    'codigo_doc'    => $codigoDoc,
                    'fecha_doc'     => $data['fecha_doc'] ?? null,
                    'descripcion'   => $data['descripcion'] ?? null,
                    'ruta'          => $storedPath,
                ]);
            }

            DB::commit();
            return $documento->fresh(['tipoDocumento']);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($storedPath) {
                Storage::disk('public')->delete($storedPath);
            }
            throw $e;
        }
    }

    private function assertEstadoValidoParaTipo(Expediente $expediente, int $tipoId): void
    {
        $estado = $expediente->id_estado instanceof EE
            ? $expediente->id_estado
            : EE::from((int)$expediente->id_estado);

        $requiereEnProcesoOReconsideracion = [8, 9];
        $requiereElevadoGerenciaSeguridad  = [10, 11];

        if (in_array($tipoId, $requiereEnProcesoOReconsideracion, true)) {
            if (!in_array($estado, [EE::EN_PROCESO, EE::EVALUANDO_RECONSIDERACION], true)) {
                throw new EstadoExpedienteInvalidoException(
                    'Para subir una Resolución (No ha lugar / Continuar), el expediente debe estar en "En Proceso" o "Evaluando Reconsideración".'
                );
            }
            return;
        }

        if (in_array($tipoId, $requiereElevadoGerenciaSeguridad, true)) {
            if ($estado !== EE::ELEVADO_GERENCIA_SEGURIDAD_CIUD) {
                throw new EstadoExpedienteInvalidoException(
                    'Para subir un Informe de Seguridad Ciudadana (Continuar / No Continuar), el expediente debe estar "Elevado a Gerencia de Seguridad Ciudadana".'
                );
            }
            return;
        }
    }
}
