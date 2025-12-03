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
    public function storeForExpediente(StoreResolucionFromSpRequest $request, int $id)
    {
        try {
            $validated = $request->validated();
            $templateKey = $validated['template'];
            $res = $this->service->crearDesdeSpFromRequest($id, $validated, $templateKey);

            // Si tiene archivo temporal, descargarlo
            if ($res->getAttribute('temp_file_path') && $res->getAttribute('temp_file_name')) {
                $response = response()->download(
                    $res->getAttribute('temp_file_path'),
                    $res->getAttribute('temp_file_name'),
                    [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'Access-Control-Expose-Headers' => 'Content-Disposition',
                    ]
                )->deleteFileAfterSend(true);
                
                return $response;
            }

            // Fallback: respuesta JSON si no hay archivo temporal
            return (new ResolucionResource($res))->response()->setStatusCode(201);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
