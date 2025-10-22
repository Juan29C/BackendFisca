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

    public function generarNumeroResolucion()
    {
        try {
            $resultado = collect(DB::select('CALL generar_numero_resolucion_simple(?)', [115]));

            if ($resultado->isNotEmpty()) {
                $numeroResolucion = $resultado[0]->numero_resolucion;

                return response()->json([
                    'numero_resolucion' => $numeroResolucion
                ]);
            } else {
                return response()->json([
                    'error' => 'No se generó ningún número de resolución.'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // POST /api/expedientes/{id}/resoluciones
    public function storeForExpediente(StoreResolucionFromSpRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $templateKey = $validated['template'];
        $res = $this->service->crearDesdeSpFromRequest($id, $validated, $templateKey);

        return (new ResolucionResource($res))->response()->setStatusCode(201);
    }
}
