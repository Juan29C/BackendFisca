<?php

namespace App\Http\Controllers;

use App\Http\Requests\Documento\PatchDocumentoRequest;
use App\Http\Requests\Documento\UploadExpedienteDocumentosRequest;
use App\Http\Resources\DocumentoResource;
use App\Http\Resources\DocumentosExpedienteResource;
use App\Models\Documento;
use App\Models\Expediente;
use App\Services\DocumentoService;
use Illuminate\Http\JsonResponse;


class DocumentoController extends Controller
{
    public function __construct(private DocumentoService $service) {}

    public function store(UploadExpedienteDocumentosRequest $request, Expediente $expediente): JsonResponse
    {
        $documento = $this->service->uploadSingle($expediente, $request->validated());

        return response()->json([
            'ok'      => true,
            'message' => 'Documento subido correctamente',
            'data'    => new DocumentoResource($documento),
        ], 201);
    }

    public function index(Expediente $expediente): JsonResponse
    {
        $docs = $expediente->documentos()->with('tipoDocumento')->latest('id')->get();

        return response()->json([
            'ok'   => true,
            'data' => DocumentosExpedienteResource::collection($docs),
        ]);
    }

    public function patch(PatchDocumentoRequest $request, int $expediente, int $id): JsonResponse
    {
        try {
            $updated = $this->service->updateDocumento($id, $request->validated());

            return response()->json([
                'ok'      => true,
                'message' => 'Documento actualizado correctamente',
                'data'    => new DocumentoResource($updated),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Documento $documento): JsonResponse
    {
        try {
            $this->service->deleteDocumento($documento->id);

            return response()->json([
                'ok'      => true,
                'message' => 'Documento eliminado correctamente',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
