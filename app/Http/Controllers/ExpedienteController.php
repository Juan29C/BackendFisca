<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expediente\ResolverApelacionRequest;
use App\Http\Requests\Expediente\UpdateExpedienteRequest;
use App\Http\Requests\StoreExpedienteRequest;
use App\Http\Resources\ExpedienteListResource;
use App\Http\Resources\ExpedienteResource;
use App\Services\ExpedienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

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
            'q'         => $request->input('q'),
            'estado_id' => $request->input('estado_id'),
        ];

        $page = $this->service->listForGrid($filters, $perPage);

        return ExpedienteListResource::collection($page)->response();
    }

    public function update(UpdateExpedienteRequest $request, int $id): JsonResponse
    {
        $exp = $this->service->updateBasic($id, $request->validated());
        if (!$exp) {
            return response()->json(['ok' => false, 'message' => 'Expediente no encontrado'], 404);
        }
        return (new ExpedienteResource($exp))->response();
    }

    // DELETE /expedientes/{id}
    public function destroy(int $id): JsonResponse
    {
        $ok = $this->service->deleteExpediente($id);
        if (!$ok) {
            return response()->json(['ok' => false, 'message' => 'Expediente no encontrado'], 404);
        }
        return response()->json(['ok' => true, 'message' => 'Expediente eliminado correctamente']);
    }


    // POST /expedientes/{id}/resolver-apelacion
    public function resolverApelacion(ResolverApelacionRequest $request, int $id): JsonResponse
    {
        $exp = $this->service->resolverApelacion($id, $request->boolean('hubo_apelacion'));
        return (new ExpedienteResource($exp))->response();
    }
}
