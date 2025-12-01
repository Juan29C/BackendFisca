<?php

namespace App\Http\Controllers;

use App\Services\DocumentoCoactivoService;
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
            'data' => $documentos,
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
            'data' => $documento,
        ]);
    }

    public function store(Request $request, int $coactivoId): JsonResponse
    {
        $validated = $request->validate([
            'id_tipo_doc_coactivo' => 'required|integer|exists:tipos_documentos_coactivo,id_tipo_doc_coactivo',
            'codigo_doc' => 'nullable|string|max:50',
            'fecha_doc' => 'nullable|date',
            'descripcion' => 'nullable|string',
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        try {
            $documento = $this->service->uploadSingle($coactivoId, $validated);

            return response()->json([
                'ok' => true,
                'message' => 'Documento subido correctamente',
                'data' => $documento,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $coactivoId, int $id): JsonResponse
    {
        $validated = $request->validate([
            'id_tipo_doc_coactivo' => 'sometimes|integer|exists:tipos_documentos_coactivo,id_tipo_doc_coactivo',
            'codigo_doc' => 'nullable|string|max:50',
            'fecha_doc' => 'nullable|date',
            'descripcion' => 'nullable|string',
            'file' => 'sometimes|file|max:10240', // 10MB max
        ]);

        try {
            $documento = $this->service->updateDocumento($id, $validated);

            return response()->json([
                'ok' => true,
                'message' => 'Documento actualizado correctamente',
                'data' => $documento,
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
}
