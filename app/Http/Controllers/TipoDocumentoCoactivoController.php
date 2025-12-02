<?php

namespace App\Http\Controllers;

use App\Http\Resources\TipoDocumentoCoactivoResource;
use App\Services\TipoDocumentoCoactivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TipoDocumentoCoactivoController extends Controller
{
    public function __construct(private TipoDocumentoCoactivoService $service) {}

    public function index(): AnonymousResourceCollection
    {
        $tipos = $this->service->getAll();
        return TipoDocumentoCoactivoResource::collection($tipos);
    }

    public function show(int $id): JsonResponse
    {
        $tipo = $this->service->getById($id);
        
        if (!$tipo) {
            return response()->json(['message' => 'Tipo de documento no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $tipo,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'descripcion' => 'required|string|max:100|unique:tipos_documentos_coactivo,descripcion',
        ]);

        $tipo = $this->service->create($validated);

        return response()->json([
            'ok' => true,
            'message' => 'Tipo de documento creado correctamente',
            'data' => $tipo,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'descripcion' => 'sometimes|string|max:100|unique:tipos_documentos_coactivo,descripcion,' . $id . ',id_tipo_doc_coactivo',
        ]);

        $tipo = $this->service->update($id, $validated);

        if (!$tipo) {
            return response()->json(['message' => 'Tipo de documento no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Tipo de documento actualizado correctamente',
            'data' => $tipo,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Tipo de documento no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Tipo de documento eliminado correctamente',
        ]);
    }
}
