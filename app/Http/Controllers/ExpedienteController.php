<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpedienteRequest;
use App\Http\Resources\ExpedienteResource;
use App\Services\ExpedienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class ExpedienteController extends Controller
{
    public function __construct(private ExpedienteService $service) {}

    public function store(StoreExpedienteRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $expediente = $this->service->crearConStoredProcedure($data);

            if (!$expediente) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No se pudo recuperar el expediente creado.'
                ], 500);
            }

            return (new ExpedienteResource($expediente))->response()->setStatusCode(201);

        } catch (QueryException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error al crear expediente',
                'error'   => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Error inesperado',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([]);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([]);
    }
}
