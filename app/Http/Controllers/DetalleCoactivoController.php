<?php

namespace App\Http\Controllers;

use App\Services\DetalleCoactivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DetalleCoactivoController extends Controller
{
    public function __construct(private DetalleCoactivoService $service) {}

    public function index(int $coactivoId): JsonResponse
    {
        $detalle = $this->service->getByCoactivo($coactivoId);
        
        return response()->json([
            'ok' => true,
            'data' => $detalle,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $detalle = $this->service->getById($id);
        
        if (!$detalle) {
            return response()->json(['message' => 'Detalle no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $detalle,
        ]);
    }

    public function store(Request $request, int $coactivoId): JsonResponse
    {
        $validated = $request->validate([
            'res_sancion_codigo' => 'nullable|string|max:50',
            'res_sancion_fecha' => 'nullable|date',
            'res_consentida_codigo' => 'nullable|string|max:50',
            'res_consentida_fecha' => 'nullable|date',
            'papeleta_codigo' => 'nullable|string|max:50',
            'papeleta_fecha' => 'nullable|date',
            'codigo_infraccion' => 'nullable|string|max:50',
            'descripcion_infraccion' => 'nullable|string',
        ]);

        $validated['id_coactivo'] = $coactivoId;

        $detalle = $this->service->create($validated);

        return response()->json([
            'ok' => true,
            'message' => 'Detalle creado correctamente',
            'data' => $detalle,
        ], 201);
    }

    public function update(Request $request, int $coactivoId, int $id): JsonResponse
    {
        $validated = $request->validate([
            'res_sancion_codigo' => 'nullable|string|max:50',
            'res_sancion_fecha' => 'nullable|date',
            'res_consentida_codigo' => 'nullable|string|max:50',
            'res_consentida_fecha' => 'nullable|date',
            'papeleta_codigo' => 'nullable|string|max:50',
            'papeleta_fecha' => 'nullable|date',
            'codigo_infraccion' => 'nullable|string|max:50',
            'descripcion_infraccion' => 'nullable|string',
        ]);

        $detalle = $this->service->update($id, $validated);

        if (!$detalle) {
            return response()->json(['message' => 'Detalle no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Detalle actualizado correctamente',
            'data' => $detalle,
        ]);
    }

    public function destroy(int $coactivoId, int $id): JsonResponse
    {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Detalle no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Detalle eliminado correctamente',
        ]);
    }
}
