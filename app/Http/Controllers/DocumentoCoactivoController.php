<?php

namespace App\Http\Controllers;

use App\Services\DocumentoCoactivoService;
use App\Http\Resources\DocumentoCoactivoResource;
use App\Http\Requests\Coactivo\StoreDocumentoCoactivoRequest;
use App\Http\Requests\Coactivo\UpdateDocumentoCoactivoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentoCoactivoController extends Controller
{
    public function __construct(private DocumentoCoactivoService $service) {}

    public function index(int $coactivoId): JsonResponse
    {
        $documentos = $this->service->getByCoactivo($coactivoId);

        return response()->json([
            'ok' => true,
            'data' => DocumentoCoactivoResource::collection($documentos),
        ]);
    }

    public function show(int $coactivoId, int $id): JsonResponse
    {
        $documento = $this->service->getById($id);
        
        if (!$documento || $documento->id_coactivo != $coactivoId) {
            return response()->json(['message' => 'Documento no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => new DocumentoCoactivoResource($documento),
        ]);
    }

    public function store(StoreDocumentoCoactivoRequest $request, int $coactivoId): JsonResponse
    {
        try {
            $documento = $this->service->uploadSingle($coactivoId, $request->validated());

            return response()->json([
                'ok' => true,
                'message' => 'Documento subido correctamente',
                'data' => new DocumentoCoactivoResource($documento),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateDocumentoCoactivoRequest $request, int $coactivoId, int $id): JsonResponse
    {
        try {
            $documento = $this->service->updateDocumento($id, $request->validated());

            return response()->json([
                'ok' => true,
                'message' => 'Documento actualizado correctamente',
                'data' => new DocumentoCoactivoResource($documento),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $coactivoId, int $id): JsonResponse
    {
        try {
            $deleted = $this->service->deleteDocumento($id);

            if (!$deleted) {
                return response()->json(['message' => 'Documento no encontrado'], 404);
            }

            return response()->json([
                'ok' => true,
                'message' => 'Documento eliminado correctamente',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera documento Word desde plantilla resolucion_coactivo_num1
     * POST /coactivos/{coactivoId}/documentos/generar-resolucion-1
     */
    public function generarResolucion1(int $coactivoId)
    {
        try {
            $result = $this->service->generarResolucion1($coactivoId);

            return response()->download($result['file_path'], $result['file_name'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera documento Word desde plantilla resolucion_coactivo_num2
     * POST /coactivos/{coactivoId}/documentos/generar-resolucion-2
     */
    public function generarResolucion2(int $coactivoId)
    {
        try {
            $result = $this->service->generarResolucion2($coactivoId);

            return response()->download($result['file_path'], $result['file_name'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera Orden de Pago Total
     * POST /coactivos/{coactivoId}/documentos/generar-orden-pago-total
     */
    public function generarOrdenPagoTotal(\App\Http\Requests\Coactivo\GenerarOrdenPagoTotalRequest $request, int $coactivoId)
    {
        try {
            $result = $this->service->generarOrdenPagoTotal($coactivoId, $request->validated());

            return response()->download($result['file_path'], $result['file_name'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera Orden de Pago Parcial
     * POST /coactivos/{coactivoId}/documentos/generar-orden-pago-parcial
     */
    public function generarOrdenPagoParcial(\App\Http\Requests\Coactivo\GenerarOrdenPagoParcialRequest $request, int $coactivoId)
    {
        try {
            $result = $this->service->generarOrdenPagoParcial($coactivoId, $request->validated());

            return response()->download($result['file_path'], $result['file_name'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera Orden de Pago Total Manual (sin expediente coactivo en sistema)
     * POST /documentos/generar-orden-pago-total-manual
     */
    public function generarOrdenPagoTotalManual(\App\Http\Requests\Coactivo\GenerarOrdenPagoTotalManualRequest $request)
    {
        try {
            $result = $this->service->generarOrdenPagoTotalManual($request->validated());

            return response()->download($result['file_path'], $result['file_name'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera Orden de Pago Parcial Manual (sin expediente coactivo en sistema)
     * POST /documentos/generar-orden-pago-parcial-manual
     */
    public function generarOrdenPagoParcialManual(\App\Http\Requests\Coactivo\GenerarOrdenPagoParcialManualRequest $request)
    {
        try {
            $result = $this->service->generarOrdenPagoParcialManual($request->validated());

            return response()->download($result['file_path'], $result['file_name'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
