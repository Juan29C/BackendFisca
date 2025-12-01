<?php

namespace App\Services;

use App\Models\DocumentoCoactivo;
use App\Repositories\DocumentoCoactivoRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DocumentoCoactivoService
{
    protected DocumentoCoactivoRepository $repository;

    public function __construct(DocumentoCoactivoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?DocumentoCoactivo
    {
        return $this->repository->find($id);
    }

    public function getByCoactivo(int $idCoactivo): Collection
    {
        return $this->repository->findByCoactivo($idCoactivo);
    }

    public function create(array $data): DocumentoCoactivo
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?DocumentoCoactivo
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $documento = $this->repository->find($id);
        if (!$documento) {
            return false;
        }

        // Eliminar el archivo físico si existe
        if (!empty($documento->ruta) && Storage::disk('public')->exists($documento->ruta)) {
            Storage::disk('public')->delete($documento->ruta);
        }

        return $this->repository->delete($id);
    }

    public function uploadSingle(int $idCoactivo, array $data): DocumentoCoactivo
    {
        DB::beginTransaction();

        try {
            $coactivo = \App\Models\Coactivo::with('expediente.administrado')->find($idCoactivo);
            if (!$coactivo) {
                throw new \Exception("Coactivo no encontrado");
            }

            $adm = $coactivo->expediente->administrado;
            $slugPersona = $adm?->ruc ?: ($adm?->dni ?: ('coactivo_' . $idCoactivo));
            $baseFolder = "coactivos/{$slugPersona}";

            $file = $data['file'];
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueName = $filename . '_' . time() . '.' . $extension;

            $path = $file->storeAs($baseFolder, $uniqueName, 'public');

            $documento = $this->repository->create([
                'id_coactivo' => $idCoactivo,
                'id_tipo_doc_coactivo' => $data['id_tipo_doc_coactivo'],
                'codigo_doc' => $data['codigo_doc'] ?? null,
                'fecha_doc' => $data['fecha_doc'] ?? null,
                'descripcion' => $data['descripcion'] ?? null,
                'ruta' => $path,
            ]);

            DB::commit();
            return $documento;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateDocumento(int $id, array $data): DocumentoCoactivo
    {
        DB::beginTransaction();

        try {
            $documento = $this->repository->find($id);
            if (!$documento) {
                throw new \Exception("Documento no encontrado");
            }

            $hasFile = isset($data['file'])
                && $data['file'] instanceof \Illuminate\Http\UploadedFile
                && $data['file']->isValid();

            if ($hasFile) {
                // Eliminar archivo anterior si existe
                if (!empty($documento->ruta) && Storage::disk('public')->exists($documento->ruta)) {
                    Storage::disk('public')->delete($documento->ruta);
                }

                $coactivo = \App\Models\Coactivo::with('expediente.administrado')->find($documento->id_coactivo);
                $adm = $coactivo->expediente->administrado;
                $slugPersona = $adm?->ruc ?: ($adm?->dni ?: ('coactivo_' . $documento->id_coactivo));
                $baseFolder = "coactivos/{$slugPersona}";

                $file = $data['file'];
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($originalName, PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . time() . '.' . $extension;

                $path = $file->storeAs($baseFolder, $uniqueName, 'public');
                $data['ruta'] = $path;
            }

            unset($data['file']);
            $updated = $this->repository->update($id, $data);

            DB::commit();
            return $updated;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteDocumento(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Genera documento Word desde plantilla usando datos del expediente coactivo
     */
    public function generarDocumentoDesdePlantilla(int $idCoactivo, string $templateKey): array
    {
        $coactivo = \App\Models\Coactivo::with(['expediente.administrado', 'detalles'])
            ->find($idCoactivo);

        if (!$coactivo) {
            throw new \Exception("Expediente coactivo no encontrado");
        }

        $administrado = $coactivo->expediente->administrado;
        if (!$administrado) {
            throw new \Exception("Administrado no encontrado en el expediente");
        }

        // Preparar variables para reemplazar en la plantilla
        $nombreCompleto = trim(($administrado->nombres ?? '') . ' ' . ($administrado->apellidos ?? '')) ?: ($administrado->razon_social ?? '');
        $documentoIdentidad = $administrado->dni ?: ($administrado->ruc ?? '');

        $variables = [
            // Claves en mayúsculas (compatibilidad previa)
            'EXPEDIENTE_COACTIVO' => $coactivo->codigo_expediente_coactivo ?? '',
            'OBLIGADO' => $nombreCompleto,
            'DNI_RUC' => $documentoIdentidad,
            'DOMICILIO' => $administrado->domicilio ?? '',
            'AUXILIAR_COACTIVO' => $coactivo->auxiliar_coactivo ?? '',
            'EJECUTOR_COACTIVO' => $coactivo->ejecutor_coactivo ?? '',
            'FECHA_INICIO' => $coactivo->fecha_inicio?->format('d/m/Y') ?? '',
            'MONTO_DEUDA' => number_format($coactivo->monto_deuda ?? 0, 2, '.', ','),
            'MONTO_COSTAS' => number_format($coactivo->monto_costas ?? 0, 2, '.', ','),
            'MONTO_GASTOS_ADMIN' => number_format($coactivo->monto_gastos_admin ?? 0, 2, '.', ','),
            'TOTAL_DEUDA' => number_format((($coactivo->monto_deuda ?? 0) + ($coactivo->monto_costas ?? 0) + ($coactivo->monto_gastos_admin ?? 0)), 2, '.', ','),

            // Claves utilizadas en tu plantilla específica (minúsculas / nombres diferentes)
            'codigo_expediente_coactivo' => $coactivo->codigo_expediente_coactivo ?? '',
            'nombre_completo_administrado' => $nombreCompleto,
            'documento_administrado' => $documentoIdentidad,
            // Nota: usar la misma grafía que tienes en el docx; si tu placeholder tiene tilde, úsala aquí.
            'dirección_administrado' => $administrado->domicilio ?? '',
            'direccion_administrado' => $administrado->domicilio ?? '',
            'auxiliar_coactivo' => $coactivo->auxiliar_coactivo ?? '',
        ];

        // Obtener ruta de la plantilla
        $templateName = config("templates.{$templateKey}");
        if (!$templateName) {
            throw new \Exception("Plantilla '{$templateKey}' no configurada");
        }

        $templatePath = storage_path("app/plantillas/{$templateName}");
        if (!file_exists($templatePath)) {
            throw new \Exception("Archivo de plantilla no encontrado: {$templatePath}");
        }

        // Generar documento
        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
        
        foreach ($variables as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // Generar nombre del archivo
        $outputFileName = 'resolucion_coactivo_' . $coactivo->id_coactivo . '_' . time() . '.docx';

        // Guardar en archivo temporal (se borrará automáticamente después de la descarga)
        $tempPath = tempnam(sys_get_temp_dir(), 'word_');
        $templateProcessor->saveAs($tempPath);

        return [
            'file_path' => $tempPath,
            'file_name' => $outputFileName,
            'variables' => $variables,
        ];
    }
}
