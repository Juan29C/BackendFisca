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

    private function buildBaseFolderFromDocumento(\App\Models\Documento $documento): string
    {
        // Carga el expediente con su administrado para armar el slugPersona
        $expediente = \App\Models\Expediente::with('administrado')
            ->find($documento->id_expediente);

        $adm = $expediente?->administrado;
        $slugPersona = $adm?->ruc ?: ($adm?->dni ?: ('expediente_' . $documento->id_expediente));

        return "expedientes/{$slugPersona}";
    }

    public function updateDocumento(int $id, array $data, ?int $expedienteId = null): Documento
    {
        DB::beginTransaction();

        try {
            $documento = $this->repository->find($id);
            if (!$documento) {
                throw new \Exception("Documento no encontrado");
            }

            // Validar que el documento pertenece al expediente si se proporciona
            if ($expedienteId !== null && $documento->id_expediente !== $expedienteId) {
                throw new \Exception("El documento no pertenece al expediente especificado");
            }

            // ¿viene archivo?
            $hasFile = isset($data['file'])
                && $data['file'] instanceof \Illuminate\Http\UploadedFile
                && $data['file']->isValid();

            if ($hasFile) {
                // 1) Reemplazo: borrar archivo anterior si existía
                if (!empty($documento->ruta) && Storage::disk('public')->exists($documento->ruta)) {
                    Storage::disk('public')->delete($documento->ruta);
                }

                // 2) Misma ruta que uploadSingle: expedientes/{dni|ruc|expediente_id}
                $baseFolder = $this->buildBaseFolderFromDocumento($documento);

                // Asegurar carpeta (opcional, por si tu driver lo requiere)
                if (!Storage::disk('public')->exists($baseFolder)) {
                    Storage::disk('public')->makeDirectory($baseFolder);
                }

                // 3) Guardar nuevo archivo
                $ext = strtolower($data['file']->getClientOriginalExtension() ?: 'pdf');
                $filename = Str::random(40) . '.' . $ext;

                // putFileAs sobreescribe si existe el mismo nombre; como es aleatorio no colisiona
                Storage::disk('public')->putFileAs($baseFolder, $data['file'], $filename);

                // Guardar ruta normalizada (sin './')
                $data['ruta'] = $baseFolder . '/' . $filename;
            }

            unset($data['file']); // nunca persistimos UploadedFile

            // Importante: asegurar que 'ruta' esté en $fillable o permitido por tu repositorio
            $this->repository->update($id, $data);

            DB::commit();
            return $this->repository->find($id)->fresh(['tipoDocumento']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }


    // ✅ Eliminar documento (y borrar archivo físico)
    public function deleteDocumento(int $id, ?int $expedienteId = null): bool
    {
        $documento = $this->repository->find($id);
        if (!$documento) {
            throw new \Exception("Documento no encontrado");
        }

        // Validar que el documento pertenece al expediente si se proporciona
        if ($expedienteId !== null && $documento->id_expediente !== $expedienteId) {
            throw new \Exception("El documento no pertenece al expediente especificado");
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
        $this->assertEstadoValidoParaTipo($expediente, (int) $data['id_tipo']);

        $tipoId    = (int) $data['id_tipo'];
        $codigoDoc = $data['codigo_doc'] ?? null;

        DB::beginTransaction();
        $storedPath = null;

        try {
            if ($this->repository->existsWithFileForDoc($expediente->id, $tipoId, $codigoDoc)) {
                throw new DocumentoDuplicadoException();
            }

            $documento = $this->repository->findDraftDoc($expediente->id, $tipoId, $codigoDoc);

            $adm         = $expediente->administrado;
            $slugPersona = $adm?->ruc ?: $adm?->dni ?: ('expediente_' . $expediente->id);
            $baseFolder  = "expedientes/{$slugPersona}";

            $originalExtension = $data['file']->getClientOriginalExtension();
            $filename = Str::random(40) . '.' . $originalExtension;

            $storedPath = Storage::disk('public')->putFileAs($baseFolder, $data['file'], $filename);

            if ($documento) {
                $documento->fill([
                    'fecha_doc'   => $data['fecha_doc'] ?? $documento->fecha_doc,
                    'descripcion' => $data['descripcion'] ?? $documento->descripcion,
                    'ruta'        => $storedPath,
                ])->save();
            } else {
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
