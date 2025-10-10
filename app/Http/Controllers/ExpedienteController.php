<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpedienteRequest;
use App\Http\Resources\ExpedienteListResource;
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
        $withHistorial  = request()->boolean('with_historial', true);
        $historialLimit = (int) request()->input('historial_limit', 0);

        $expediente = $this->service->getDetailed($id, $withHistorial, $historialLimit);

        if (!$expediente) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return (new ExpedienteResource($expediente))->response();
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $filters = [
            'q'         => $request->input('q'),          // búsqueda libre (código, dni, ruc, nombres, apellidos, razón social)
            'estado_id' => $request->input('estado_id'),  // filtrar por id_estado
        ];

        $page = $this->service->listForGrid($filters, $perPage);

        // Devuelve solo los campos solicitados (id, codigo, ciudadano, dni, domicilio, estado)
        return ExpedienteListResource::collection($page)->response();
    }
}
