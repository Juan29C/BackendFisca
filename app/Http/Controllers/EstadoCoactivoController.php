<?php

namespace App\Http\Controllers;

use App\Services\EstadoCoactivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstadoCoactivoController extends Controller
{
    public function __construct(private EstadoCoactivoService $service) {}

    public function index(): JsonResponse
    {
        $estados = $this->service->getAll();
        return response()->json([
            'ok' => true,
            'data' => $estados,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $estado = $this->service->getById($id);
        
        if (!$estado) {
            return response()->json(['message' => 'Estado no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $estado,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:estados_coactivo,nombre',
            'descripcion' => 'nullable|string',
        ]);

        $estado = $this->service->create($validated);

        return response()->json([
            'ok' => true,
            'message' => 'Estado creado correctamente',
            'data' => $estado,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100|unique:estados_coactivo,nombre,' . $id,
            'descripcion' => 'nullable|string',
        ]);

        $estado = $this->service->update($id, $validated);

        if (!$estado) {
            return response()->json(['message' => 'Estado no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Estado actualizado correctamente',
            'data' => $estado,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Estado no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Estado eliminado correctamente',
        ]);
    }
}
