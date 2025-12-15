<?php

namespace App\Http\Controllers;

use App\Services\CoactivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReporteCoactivoController extends Controller
{
    public function __construct(private CoactivoService $coactivoService) {}

    /**
     * Genera reporte PDF de estadísticas de coactivo
     * GET /reportes/coactivo/estadisticas-pdf?fecha_inicio=2024-01-01&fecha_fin=2024-12-31
     */
    public function generarReporteEstadisticasPDF(Request $request)
    {
        try {
            $fechaInicio = $request->query('fecha_inicio');
            $fechaFin = $request->query('fecha_fin');

            $estadisticas = $this->coactivoService->getDashboardEstadisticasFinancieras($fechaInicio, $fechaFin);

            $data = [
                'estadisticas' => $estadisticas,
                'fecha_inicio' => $fechaInicio ?: 'Todo el tiempo',
                'fecha_fin' => $fechaFin ?: date('Y-m-d'),
                'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            ];

            $pdf = Pdf::loadView('reportes.coactivo-estadisticas', $data);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->download('reporte-coactivo-estadisticas-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera reporte Excel de estadísticas de coactivo
     * GET /reportes/coactivo/estadisticas-excel?fecha_inicio=2024-01-01&fecha_fin=2024-12-31
     */
    public function generarReporteEstadisticasExcel(Request $request)
    {
        try {
            $fechaInicio = $request->query('fecha_inicio');
            $fechaFin = $request->query('fecha_fin');

            $estadisticas = $this->coactivoService->getDashboardEstadisticasFinancieras($fechaInicio, $fechaFin);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Título
            $sheet->setCellValue('A1', 'REPORTE ESTADÍSTICAS COACTIVO');
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Información del período
            $sheet->setCellValue('A2', 'Período: ' . ($fechaInicio ?: 'Todo el tiempo') . ' - ' . ($fechaFin ?: date('Y-m-d')));
            $sheet->mergeCells('A2:F2');
            $sheet->setCellValue('A3', 'Fecha generación: ' . now()->format('d/m/Y H:i:s'));
            $sheet->mergeCells('A3:F3');

            // Resumen financiero
            $row = 5;
            $sheet->setCellValue('A' . $row, 'RESUMEN FINANCIERO');
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4CAF50');
            $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');

            $row++;
            $resumenData = [
                ['Total Expedientes', $estadisticas['resumen']['total_expedientes']],
                ['Total Deuda', 'S/ ' . number_format($estadisticas['resumen']['monto_total_deuda'], 2)],
                ['Total Recaudado', 'S/ ' . number_format($estadisticas['resumen']['monto_total_recaudado'], 2)],
                ['Total Pendiente', 'S/ ' . number_format($estadisticas['resumen']['monto_total_pendiente'], 2)],
                ['% Recaudación', $estadisticas['resumen']['porcentaje_recaudacion'] . '%'],
                ['En Ejecución', $estadisticas['resumen']['en_ejecucion']],
                ['Archivados', $estadisticas['resumen']['archivados']],
                ['Suspendidos', $estadisticas['resumen']['suspendidos']],
            ];

            foreach ($resumenData as $item) {
                $sheet->setCellValue('A' . $row, $item[0]);
                $sheet->setCellValue('B' . $row, $item[1]);
                $row++;
            }

            // Distribución mensual
            $row += 2;
            $sheet->setCellValue('A' . $row, 'DISTRIBUCIÓN MENSUAL');
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2196F3');
            $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');

            $row++;
            $headers = ['Mes', 'Cantidad', 'Monto Total', 'Monto Recaudado'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $col++;
            }

            $row++;
            foreach ($estadisticas['distribucion_mensual'] as $item) {
                $sheet->setCellValue('A' . $row, $item['mes']);
                $sheet->setCellValue('B' . $row, $item['cantidad']);
                $sheet->setCellValue('C' . $row, 'S/ ' . number_format($item['monto_total'], 2));
                $sheet->setCellValue('D' . $row, 'S/ ' . number_format($item['monto_recaudado'], 2));
                $row++;
            }

            // Mayores deudas
            $row += 2;
            $sheet->setCellValue('A' . $row, 'TOP MAYORES DEUDAS PENDIENTES');
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF5722');
            $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');

            $row++;
            $headers = ['Código', 'Administrado', 'Deuda Pendiente'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $col++;
            }

            $row++;
            foreach ($estadisticas['mayores_deudas'] as $deuda) {
                $sheet->setCellValue('A' . $row, $deuda['codigo']);
                $sheet->setCellValue('B' . $row, $deuda['administrado']);
                $sheet->setCellValue('C' . $row, 'S/ ' . number_format($deuda['deuda_pendiente'], 2));
                $row++;
            }

            // Ajustar anchos
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Generar archivo
            $writer = new Xlsx($spreadsheet);
            $fileName = 'reporte-coactivo-estadisticas-' . date('Y-m-d') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), $fileName);
            $writer->save($tempFile);

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera reporte PDF de expedientes coactivos
     * GET /reportes/coactivo/expedientes-pdf?estado=En Ejecución
     */
    public function generarReporteExpedientesPDF(Request $request)
    {
        try {
            $filters = $request->only(['estado', 'q']);
            $expedientes = $this->coactivoService->getAllWithRelations();

            // Aplicar filtros manualmente si es necesario
            if (!empty($filters['estado'])) {
                $expedientes = $expedientes->filter(function ($exp) use ($filters) {
                    return $exp->estado === $filters['estado'];
                });
            }

            $data = [
                'expedientes' => $expedientes,
                'filtros' => $filters,
                'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            ];

            $pdf = Pdf::loadView('reportes.coactivo-expedientes', $data);
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download('reporte-expedientes-coactivo-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
