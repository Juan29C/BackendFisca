<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntidadBancaria\StoreEntidadBancariaRequest;
use App\Http\Requests\EntidadBancaria\UpdateEntidadBancariaRequest;
use App\Http\Resources\EntidadBancariaResource;
use App\Services\EntidadBancariaService;
use App\Traits\HasRoleAuthorization;
use Illuminate\Http\JsonResponse;

class EntidadBancariaController extends Controller
{

    public function __construct(private EntidadBancariaService $service) {}

    public function index(): JsonResponse
    {

        $entidades = $this->service->getAll();
        
        return response()->json([
            'ok' => true,
            'data' => EntidadBancariaResource::collection($entidades),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $entidad = $this->service->getById($id);
        
        if (!$entidad) {
            return response()->json(['message' => 'Entidad bancaria no encontrada'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => new EntidadBancariaResource($entidad),
        ]);
    }

    public function store(StoreEntidadBancariaRequest $request): JsonResponse
    {

        $entidad = $this->service->create($request->validated());

        return response()->json([
            'ok' => true,
            'message' => 'Entidad bancaria creada correctamente',
            'data' => new EntidadBancariaResource($entidad),
        ], 201);
    }

    public function update(UpdateEntidadBancariaRequest $request, int $id): JsonResponse
    {

        $entidad = $this->service->update($id, $request->validated());

        if (!$entidad) {
            return response()->json(['message' => 'Entidad bancaria no encontrada'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Entidad bancaria actualizada correctamente',
            'data' => new EntidadBancariaResource($entidad),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {


        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Entidad bancaria no encontrada'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Entidad bancaria eliminada correctamente',
        ]);
    }
}
