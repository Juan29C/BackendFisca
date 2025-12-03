<?php

namespace App\Services;

use App\Models\Coactivo;
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
        $disk = config('filesystems.default');
        if (!empty($documento->ruta) && Storage::disk($disk)->exists($documento->ruta)) {
            Storage::disk($disk)->delete($documento->ruta);
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

            // Validar si es un recibo de pago y tiene monto
            if (isset($data['monto_pagado']) && $data['monto_pagado'] > 0) {
                $tipoDoc = \App\Models\TipoDocumentoCoactivo::find($data['id_tipo_doc_coactivo']);
                if ($tipoDoc && stripos($tipoDoc->descripcion, 'RECIBO') !== false) {
                    // Calcular total y saldo
                    $montoTotal = bcadd(bcadd($coactivo->monto_deuda, $coactivo->monto_costas, 2), $coactivo->monto_gastos_admin, 2);
                    $montoPagadoActual = $coactivo->monto_pagado ?? 0;
                    $saldoPendiente = bcsub($montoTotal, $montoPagadoActual, 2);
                    
                    // Validar que el pago no exceda el saldo
                    if (bccomp($data['monto_pagado'], $saldoPendiente, 2) > 0) {
                        throw new \Exception("El monto pagado (S/ {$data['monto_pagado']}) excede el saldo pendiente (S/ {$saldoPendiente})");
                    }
                    
                    // Actualizar monto_pagado del coactivo
                    $nuevoMontoPagado = bcadd($montoPagadoActual, $data['monto_pagado'], 2);
                    $coactivo->monto_pagado = $nuevoMontoPagado;
                    $coactivo->save();
                }
            }

            $adm = $coactivo->expediente->administrado;
            $slugPersona = $adm?->ruc ?: ($adm?->dni ?: ('coactivo_' . $idCoactivo));
            $baseFolder = "expedientesCoactivo/{$slugPersona}";

            $file = $data['file'];
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueName = $filename . '_' . time() . '.' . $extension;

            $disk = config('filesystems.default');
            $path = $file->storeAs($baseFolder, $uniqueName, $disk);

            // Si es un recibo de pago con monto, guardamos el monto en la descripción
            $descripcion = null;
            if (isset($data['monto_pagado']) && $data['monto_pagado'] > 0) {
                $descripcion = 'MONTO_PAGADO:' . $data['monto_pagado'];
            }

            $documento = $this->repository->create([
                'id_coactivo' => $idCoactivo,
                'id_tipo_doc_coactivo' => $data['id_tipo_doc_coactivo'],
                'codigo_doc' => $data['codigo_doc'] ?? null,
                'fecha_doc' => $data['fecha_doc'] ?? null,
                'descripcion' => $descripcion,
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
                $disk = config('filesystems.default');
                if (!empty($documento->ruta) && Storage::disk($disk)->exists($documento->ruta)) {
                    Storage::disk($disk)->delete($documento->ruta);
                }

                $coactivo = \App\Models\Coactivo::with('expediente.administrado')->find($documento->id_coactivo);
                $adm = $coactivo->expediente->administrado;
                $slugPersona = $adm?->ruc ?: ($adm?->dni ?: ('coactivo_' . $documento->id_coactivo));
                $baseFolder = "expedientesCoactivo/{$slugPersona}";

                $file = $data['file'];
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = pathinfo($originalName, PATHINFO_FILENAME);
                $uniqueName = $filename . '_' . time() . '.' . $extension;

                $disk = config('filesystems.default');
                $path = $file->storeAs($baseFolder, $uniqueName, $disk);
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
        DB::beginTransaction();
        
        try {
            $documento = $this->repository->find($id);
            if (!$documento) {
                return false;
            }

            // Si el documento tiene monto pagado registrado, restarlo del coactivo
            if ($documento->descripcion && strpos($documento->descripcion, 'MONTO_PAGADO:') === 0) {
                $montoPagado = floatval(str_replace('MONTO_PAGADO:', '', $documento->descripcion));
                
                if ($montoPagado > 0) {
                    $coactivo = \App\Models\Coactivo::find($documento->id_coactivo);
                    if ($coactivo) {
                        $nuevoMontoPagado = bcsub($coactivo->monto_pagado, $montoPagado, 2);
                        // Asegurar que no sea negativo
                        $coactivo->monto_pagado = max(0, $nuevoMontoPagado);
                        $coactivo->save();
                    }
                }
            }

            $result = $this->delete($id);
            DB::commit();
            return $result;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
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
        // Usar `fecha_inicio` del coactivo si existe; si no, usar la fecha actual
        $sourceDate = $coactivo->fecha_inicio ?? now();
        if (!($sourceDate instanceof \Illuminate\Support\Carbon)) {
            try {
                $sourceDate = \Illuminate\Support\Carbon::parse($sourceDate);
            } catch (\Throwable $e) {
                $sourceDate = now();
            }
        }

        $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];
        $fechaResCoactivo = sprintf('%02d %s DEL %d', $sourceDate->day, $meses[(int)$sourceDate->month] ?? strtoupper($sourceDate->format('F')), $sourceDate->year);

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

    /**
     * Wrapper específico para la resolución 1
     */
    public function generarResolucion1(int $idCoactivo): array
    {
        return $this->generarDocumentoDesdePlantilla($idCoactivo, 'resolucionCoactivoNum1');
    }

    /**
     * Wrapper específico para la resolución 2
     */
    public function generarResolucion2(int $idCoactivo): array
    {
        // Generar documento específicamente para la plantilla resolucionCoactivoNum2
        $coactivo = \App\Models\Coactivo::with(['expediente.administrado', 'detalles'])
            ->find($idCoactivo);

        if (!$coactivo) {
            throw new \Exception("Expediente coactivo no encontrado");
        }

        $administrado = $coactivo->expediente->administrado;
        if (!$administrado) {
            throw new \Exception("Administrado no encontrado en el expediente");
        }

        $nombreCompleto = trim(($administrado->nombres ?? '') . ' ' . ($administrado->apellidos ?? '')) ?: ($administrado->razon_social ?? '');
        $nombreCompletoUpper = mb_strtoupper($nombreCompleto, 'UTF-8');
        $documentoIdentidad = $administrado->dni ?: ($administrado->ruc ?? '');
        $direccionUpper = mb_strtoupper($administrado->domicilio ?? '', 'UTF-8');
        $ejecutorUpper = mb_strtoupper($coactivo->ejecutor_coactivo ?? 'MUNICIPALIDAD DISTRITAL DE NUEVO CHIMBOTE', 'UTF-8');

        // Montos
        $montoDeuda = $coactivo->monto_deuda ?? 0;
        $montoCostas = $coactivo->monto_costas ?? 0;
        $montoGastosAdmin = $coactivo->monto_gastos_admin ?? 0;
        $montoTotal = $montoDeuda + $montoCostas + $montoGastosAdmin;

        // Fecha inicio del coactivo en formato "10 FEBRERO DEL 2023"
        $sourceDate = $coactivo->fecha_inicio ?? now();
        if (!($sourceDate instanceof \Illuminate\Support\Carbon)) {
            try {
                $sourceDate = \Illuminate\Support\Carbon::parse($sourceDate);
            } catch (\Throwable $e) {
                $sourceDate = now();
            }
        }
        $meses = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];
        $fechaInicioFormateada = sprintf('%02d %s DEL %d', $sourceDate->day, $meses[(int)$sourceDate->month] ?? strtoupper($sourceDate->format('F')), $sourceDate->year);

        // Fecha de hoy para encabezado: "10 FEBRERO DEL 2023"
        $today = now();
        $fechaHoyFormateada = sprintf('%02d %s DEL %d', $today->day, $meses[(int)$today->month] ?? strtoupper($today->format('F')), $today->year);

        // Convertir monto a palabras (es)
        $montoPalabras = '';
        try {
            $fmt = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
            $whole = floor($montoTotal);
            $decimals = round(($montoTotal - $whole) * 100);
            $words = $fmt->format($whole);
            $words = mb_strtoupper(trim($words), 'UTF-8');
            // Siempre incluir la parte fraccionaria /100; si no hay decimales, usar 00
            $montoPalabras = $words . ' CON ' . sprintf('%02d', $decimals) . '/100 SOLES';
        } catch (\Throwable $e) {
            // Fallback: si intl no está disponible, usar conversión propia para la parte entera
            $whole = floor($montoTotal);
            $decimals = round(($montoTotal - $whole) * 100);
            $words = mb_strtoupper($this->numberToWordsEs((int)$whole), 'UTF-8');
            if ($words === '') {
                $montoPalabras = '';
            } else {
                // Igual que arriba: siempre incluir /100
                $montoPalabras = $words . ' CON ' . sprintf('%02d', $decimals) . '/100 SOLES';
            }
        }

        $variables = [
            'cod_expediente_coactivo' => $coactivo->codigo_expediente_coactivo ?? '',
            'nombre_completo' => $nombreCompletoUpper,
            'documento' => $documentoIdentidad,
            'direccion' => $direccionUpper,
            'auxiliar_coactivo' => $coactivo->auxiliar_coactivo ?? '',
            'ejecutor_coactivo' => $ejecutorUpper,
            'monto_total_palabras' => $montoPalabras,
            'monto_total' => number_format($montoTotal, 2, '.', ','),
            'fecha_incio_coactivos' => $fechaInicioFormateada,
            'fecha_inicio_coactivos' => $fechaInicioFormateada,
            'fecha_hoy' => $fechaHoyFormateada,
        ];

        // Obtener ruta de la plantilla
        $templateName = config('templates.resolucionCoactivoNum2');
        if (!$templateName) {
            throw new \Exception("Plantilla 'resolucionCoactivoNum2' no configurada");
        }

        $templatePath = storage_path("app/plantillas/{$templateName}");
        if (!file_exists($templatePath)) {
            throw new \Exception("Archivo de plantilla no encontrado: {$templatePath}");
        }

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
        foreach ($variables as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        $outputFileName = 'resolucion_coactivo_num2_' . $coactivo->id_coactivo . '_' . time() . '.docx';
        $tempPath = tempnam(sys_get_temp_dir(), 'word_');
        $templateProcessor->saveAs($tempPath);

        return [
            'file_path' => $tempPath,
            'file_name' => $outputFileName,
            'variables' => $variables,
        ];
    }

    /**
     * Genera Orden de Pago Total
     */
    public function generarOrdenPagoTotal(int $idCoactivo, array $data): array
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

        $nombreCompleto = trim(($administrado->nombres ?? '') . ' ' . ($administrado->apellidos ?? '')) ?: ($administrado->razon_social ?? '');
        $nombreCompletoUpper = mb_strtoupper($nombreCompleto, 'UTF-8');
        $documentoIdentidad = $administrado->dni ?: ($administrado->ruc ?? '');

        // Primer detalle para cod_sancion
        $detalle = $coactivo->detalles->first();
        $codSancion = $detalle->res_sancion_codigo ?? '';

        // Montos
        $montoDeuda = $coactivo->monto_deuda ?? 0;
        $montoCostas = $coactivo->monto_costas ?? 0;
        $montoGastosAdmin = $coactivo->monto_gastos_admin ?? 0;
        $montoTotal = $montoDeuda + $montoCostas + $montoGastosAdmin;

        $variables = [
            'cod_expediente_coactivo' => $coactivo->codigo_expediente_coactivo ?? '',
            'nombre_completo' => $nombreCompletoUpper,
            'documento' => $documentoIdentidad,
            'direccion' => $administrado->domicilio ?? '',
            'cod_sancion' => $codSancion,
            'monto_total' => number_format($montoTotal, 2, '.', ','),
            'monto_final' => number_format($data['monto_final'], 2, '.', ','),
            'fecha_orden_pago' => \Carbon\Carbon::parse($data['fecha_orden_pago'])->format('d/m/Y'),
            'porcentaje_amnistia' => $data['porcentaje_amnistia'] ?? '0',
            'monto_descuento_amnistia' => number_format($data['monto_descuento_amnistia'] ?? 0, 2, '.', ','),
            'ordenanza' => $data['ordenanza'] ?? '',
        ];

        $templateName = config('templates.ordenPagoTotal');
        if (!$templateName) {
            throw new \Exception("Plantilla 'ordenPagoTotal' no configurada");
        }

        $templatePath = storage_path("app/plantillas/{$templateName}");
        if (!file_exists($templatePath)) {
            throw new \Exception("Archivo de plantilla no encontrado: {$templatePath}");
        }

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
        foreach ($variables as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        $outputFileName = 'orden_pago_total_' . $coactivo->id_coactivo . '_' . time() . '.docx';
        $tempPath = tempnam(sys_get_temp_dir(), 'word_');
        $templateProcessor->saveAs($tempPath);

        return [
            'file_path' => $tempPath,
            'file_name' => $outputFileName,
            'variables' => $variables,
        ];
    }

    /**
     * Genera Orden de Pago Parcial
     */
    public function generarOrdenPagoParcial(int $idCoactivo, array $data): array
    {
        $coactivo = Coactivo::with(['expediente.administrado', 'detalles'])
            ->find($idCoactivo);

        if (!$coactivo) {
            throw new \Exception("Expediente coactivo no encontrado");
        }

        $administrado = $coactivo->expediente->administrado;
        if (!$administrado) {
            throw new \Exception("Administrado no encontrado en el expediente");
        }

        $nombreCompleto = trim(($administrado->nombres ?? '') . ' ' . ($administrado->apellidos ?? '')) ?: ($administrado->razon_social ?? '');
        $nombreCompletoUpper = mb_strtoupper($nombreCompleto, 'UTF-8');
        $documentoIdentidad = $administrado->dni ?: ($administrado->ruc ?? '');

        // Primer detalle para cod_sancion
        $detalle = $coactivo->detalles->first();
        $codSancion = $detalle->res_sancion_codigo ?? '';

        // Montos
        $montoDeuda = $coactivo->monto_deuda ?? 0;
        $montoCostas = $coactivo->monto_costas ?? 0;
        $montoGastosAdmin = $coactivo->monto_gastos_admin ?? 0;
        $montoTotal = $montoDeuda + $montoCostas + $montoGastosAdmin;

        $variables = [
            'cod_expediente_coactivo' => $coactivo->codigo_expediente_coactivo ?? '',
            'nombre_completo' => $nombreCompletoUpper,
            'documento' => $documentoIdentidad,
            'direccion' => $administrado->domicilio ?? '',
            'cod_sancion' => $codSancion,
            'monto_total' => number_format($montoTotal, 2, '.', ','),
            'monto_pagado' => number_format($data['monto_pagado'], 2, '.', ','),
            'fecha_pago' => \Carbon\Carbon::parse($data['fecha_pago'])->format('d/m/Y'),
            'monto_saldo_pendiente' => number_format($data['monto_saldo_pendiente'], 2, '.', ','),
            'ordenanza' => $data['ordenanza'] ?? '',
        ];

        $templateName = config('templates.ordenPagoParcial');
        if (!$templateName) {
            throw new \Exception("Plantilla 'ordenPagoParcial' no configurada");
        }

        $templatePath = storage_path("app/plantillas/{$templateName}");
        if (!file_exists($templatePath)) {
            throw new \Exception("Archivo de plantilla no encontrado: {$templatePath}");
        }

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
        foreach ($variables as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        $outputFileName = 'orden_pago_parcial_' . $coactivo->id_coactivo . '_' . time() . '.docx';
        $tempPath = tempnam(sys_get_temp_dir(), 'word_');
        $templateProcessor->saveAs($tempPath);

        return [
            'file_path' => $tempPath,
            'file_name' => $outputFileName,
            'variables' => $variables,
        ];
    }

    /**
     * Genera Orden de Pago Total (Manual - sin expediente coactivo en sistema)
     */
    public function generarOrdenPagoTotalManual(array $data): array
    {
        // Convertir nombre completo a mayúsculas
        $nombreCompletoUpper = mb_strtoupper($data['nombre_completo'], 'UTF-8');
        $direccionUpper = mb_strtoupper($data['direccion'], 'UTF-8');

        $variables = [
            'cod_expediente_coactivo' => $data['cod_expediente_coactivo'],
            'nombre_completo' => $nombreCompletoUpper,
            'documento' => $data['documento'],
            'cod_sancion' => $data['cod_sancion'],
            'direccion' => $direccionUpper,
            'monto_total' => number_format($data['monto_total'], 2, '.', ','),
            'monto_final' => number_format($data['monto_final'], 2, '.', ','),
            'fecha_orden_pago' => \Carbon\Carbon::parse($data['fecha_orden_pago'])->format('d/m/Y'),
            'porcentaje_amnistia' => $data['porcentaje_amnistia'] ?? '0',
            'monto_descuento_amnistia' => number_format($data['monto_descuento_amnistia'] ?? 0, 2, '.', ','),
            'ordenanza' => $data['ordenanza'] ?? '',
        ];

        $templateName = config('templates.ordenPagoTotal');
        if (!$templateName) {
            throw new \Exception("Plantilla 'ordenPagoTotal' no configurada");
        }

        $templatePath = storage_path("app/plantillas/{$templateName}");
        if (!file_exists($templatePath)) {
            throw new \Exception("Archivo de plantilla no encontrado: {$templatePath}");
        }

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
        foreach ($variables as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        $outputFileName = 'orden_pago_total_manual_' . time() . '.docx';
        $tempPath = tempnam(sys_get_temp_dir(), 'word_');
        $templateProcessor->saveAs($tempPath);

        return [
            'file_path' => $tempPath,
            'file_name' => $outputFileName,
            'variables' => $variables,
        ];
    }

    /**
     * Genera Orden de Pago Parcial (Manual - sin expediente coactivo en sistema)
     */
    public function generarOrdenPagoParcialManual(array $data): array
    {
        // Convertir nombre completo y dirección a mayúsculas
        $nombreCompletoUpper = mb_strtoupper($data['nombre_completo'], 'UTF-8');
        $direccionUpper = mb_strtoupper($data['direccion'], 'UTF-8');

        $variables = [
            'cod_expediente_coactivo' => $data['cod_expediente_coactivo'],
            'nombre_completo' => $nombreCompletoUpper,
            'documento' => $data['documento'],
            'direccion' => $direccionUpper,
            'cod_sancion' => $data['cod_sancion'],
            'monto_total' => number_format($data['monto_total'], 2, '.', ','),
            'monto_pagado' => number_format($data['monto_pagado'], 2, '.', ','),
            'fecha_pago' => \Carbon\Carbon::parse($data['fecha_pago'])->format('d/m/Y'),
            'monto_saldo_pendiente' => number_format($data['monto_saldo_pendiente'], 2, '.', ','),
            'ordenanza' => $data['ordenanza'] ?? '',
        ];

        $templateName = config('templates.ordenPagoParcial');
        if (!$templateName) {
            throw new \Exception("Plantilla 'ordenPagoParcial' no configurada");
        }

        $templatePath = storage_path("app/plantillas/{$templateName}");
        if (!file_exists($templatePath)) {
            throw new \Exception("Archivo de plantilla no encontrado: {$templatePath}");
        }

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
        foreach ($variables as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        $outputFileName = 'orden_pago_parcial_manual_' . time() . '.docx';
        $tempPath = tempnam(sys_get_temp_dir(), 'word_');
        $templateProcessor->saveAs($tempPath);

        return [
            'file_path' => $tempPath,
            'file_name' => $outputFileName,
            'variables' => $variables,
        ];
    }

    /**
     * Convierte un número entero (0..999999999) a palabras en español (sin la palabra SOLES).
     * Resultado en minúsculas, por eso envolvemos con mb_strtoupper donde se requiera.
     */
    private function numberToWordsEs(int $number): string
    {
        if ($number === 0) {
            return 'cero';
        }

        $units = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $teens = [10 => 'diez', 11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince', 16 => 'dieciseis', 17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve'];
        $tens = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $hundreds = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        $parts = [];

        // millones
        if ($number >= 1000000) {
            $millions = intdiv($number, 1000000);
            $number = $number % 1000000;
            if ($millions == 1) {
                $parts[] = 'un millon';
            } else {
                $parts[] = $this->numberToWordsEs($millions) . ' millones';
            }
        }

        // miles
        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            $number = $number % 1000;
            if ($thousands == 1) {
                $parts[] = 'mil';
            } else {
                $parts[] = $this->numberToWordsEs($thousands) . ' mil';
            }
        }

        // centenas
        if ($number >= 100) {
            if ($number == 100) {
                $parts[] = 'cien';
                $number = 0;
            } else {
                $h = intdiv($number, 100);
                $parts[] = $hundreds[$h];
                $number = $number % 100;
            }
        }

        // decenas y unidades
        if ($number >= 20) {
            $t = intdiv($number, 10);
            $u = $number % 10;
            if ($t == 2 && $u > 0) {
                // veinte -> veintiuno, veintidos (unir)
                $parts[] = 'veinti' . $units[$u];
            } else {
                $part = $tens[$t];
                if ($u > 0) {
                    $part .= ' y ' . $units[$u];
                }
                $parts[] = $part;
            }
        } elseif ($number >= 10) {
            $parts[] = $teens[$number];
        } elseif ($number > 0) {
            $parts[] = $units[$number];
        }

        return trim(implode(' ', $parts));
    }
}
