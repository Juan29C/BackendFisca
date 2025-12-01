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

        // Primer detalle (si existe) para extraer papeleta/resolución/infracción
        $detalle = $coactivo->detalles->first();

        $tipoPapeleta = $detalle->tipo_papeleta ?? 'Papeleta de Infracción Administrativa';
        $codPapeleta = $detalle->papeleta_codigo ?? '';
        $fechaPapeleta = isset($detalle->papeleta_fecha) ? $detalle->papeleta_fecha->format('d/m/Y') : '';
        $codSancion = $detalle->res_sancion_codigo ?? '';
        $fechaSancion = isset($detalle->res_sancion_fecha) ? $detalle->res_sancion_fecha->format('d/m/Y') : '';
        $codConsentida = $detalle->res_consentida_codigo ?? '';
        $fechaConsentida = isset($detalle->res_consentida_fecha) ? $detalle->res_consentida_fecha->format('d/m/Y') : '';
        $codInfraccion = $detalle->codigo_infraccion ?? '';
        $descripcionPapeleta = $detalle->descripcion_infraccion ?? '';

        // Montos
        $montoDeuda = $coactivo->monto_deuda ?? 0;
        $montoCostas = $coactivo->monto_costas ?? 0;
        $montoGastosAdmin = $coactivo->monto_gastos_admin ?? 0;
        $montoTotal = $montoDeuda + $montoCostas + $montoGastosAdmin;

        // Fecha de resolución en formato "04 DICIEMBRE DEL 2023"
        $now = now();
        $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];
        $fechaResCoactivo = sprintf('%02d %s DEL %d', $now->day, $meses[(int)$now->month] ?? strtoupper($now->format('F')), $now->year);

        // Forzar mayúsculas en campos requeridos
        $nombreCompletoUpper = mb_strtoupper($nombreCompleto, 'UTF-8');
        $direccionUpper = mb_strtoupper($administrado->domicilio ?? '', 'UTF-8');
        $ejecutorUpper = mb_strtoupper($coactivo->ejecutor_coactivo ?? '', 'UTF-8');

        $variables = [
            // Nombres solicitados por la plantilla
            'cod_expediente_coactivo' => $coactivo->codigo_expediente_coactivo ?? '',
            'codigo_expediente_coactivo' => $coactivo->codigo_expediente_coactivo ?? '',

            'nombre_completo' => $nombreCompletoUpper,
            'nombre_completo_administrado' => $nombreCompletoUpper,
            'OBLIGADO' => $nombreCompletoUpper,

            'documento' => $documentoIdentidad,
            'documento_administrado' => $documentoIdentidad,
            'DNI_RUC' => $documentoIdentidad,

            'direccion' => $direccionUpper,
            'dirección_administrado' => $direccionUpper,
            'direccion_administrado' => $direccionUpper,
            'DOMICILIO' => $direccionUpper,

            'ejecutor_coactivo' => $ejecutorUpper,
            'EJECUTOR_COACTIVO' => $ejecutorUpper,

            'auxiliar_coactivo' => $coactivo->auxiliar_coactivo ?? '',

            // Fecha de resolución especial
            'fecha_res_coactivo' => $fechaResCoactivo,

            // Papeleta / resoluciones / infracción
            'tipo_papeleta' => $tipoPapeleta,
            'cod_papeleta' => $codPapeleta,
            'fecha_papeleta' => $fechaPapeleta,
            'cod_sancion' => $codSancion,
            'fecha_sancion' => $fechaSancion,
            'cod_consentida' => $codConsentida,
            'fecha_consentida' => $fechaConsentida,
            'cod_infraccion' => $codInfraccion,
            'DESCRIPCION_PAPELETA' => mb_strtoupper($descripcionPapeleta, 'UTF-8'),

            // Montos
            'monto_deuda' => number_format($montoDeuda, 2, '.', ','),
            'monto_gatos_admin' => number_format($montoGastosAdmin, 2, '.', ','),
            'monto_gastos_admin' => number_format($montoGastosAdmin, 2, '.', ','),
            'monto_costas' => number_format($montoCostas, 2, '.', ','),
            'monto_total' => number_format($montoTotal, 2, '.', ','),

            // Compatibilidad adicional
            'EXPEDIENTE_COACTIVO' => $coactivo->codigo_expediente_coactivo ?? '',
            'FECHA_INICIO' => $coactivo->fecha_inicio?->format('d/m/Y') ?? '',
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
