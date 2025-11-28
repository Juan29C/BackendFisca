<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResolucionFromSpRequest;
use App\Http\Resources\ResolucionResource;
use App\Models\Expediente;
use App\Services\ResolucionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResolucionController extends Controller
{

    public function __construct(private ResolucionService $service) {}

    // POST /api/expedientes/{id}/resoluciones - Crear resolución 
    public function storeForExpediente(StoreResolucionFromSpRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $templateKey = $validated['template'];
        $res = $this->service->crearDesdeSpFromRequest($id, $validated, $templateKey);

        return (new ResolucionResource($res))->response()->setStatusCode(201);
    }
}
