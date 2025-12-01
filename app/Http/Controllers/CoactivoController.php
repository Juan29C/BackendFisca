<?php

namespace App\Http\Controllers;

use App\Http\Requests\Coactivo\VincularExpedienteCoactivoRequest;
use App\Http\Resources\CoactivoListResource;
use App\Http\Resources\CoactivoResource;
use App\Services\CoactivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoactivoController extends Controller
{
    public function __construct(private CoactivoService $service) {}

    public function index(Request $request): JsonResponse
    {
        // Obtener todos los coactivos con sus relaciones
        $coactivos = $this->service->getAllWithRelations();

        return response()->json([
            'ok' => true,
            'data' => CoactivoListResource::collection($coactivos),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $coactivo = $this->service->getDetailed($id);
        
        if (!$coactivo) {
            return response()->json(['message' => 'Coactivo no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => new CoactivoResource($coactivo),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo_expediente_coactivo' => 'required|string|max:100|unique:coactivos,codigo_expediente_coactivo',
            'id_expediente' => 'required|integer|exists:expediente,id',
            'ejecutor_coactivo' => 'required|string|max:200',
            'auxiliar_coactivo' => 'nullable|string|max:200',
            'fecha_inicio' => 'nullable|date',
            'monto_deuda' => 'nullable|numeric|min:0',
            'monto_costas' => 'nullable|numeric|min:0',
            'monto_gastos_admin' => 'nullable|numeric|min:0',
            'estado' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
        ]);

        $coactivo = $this->service->create($validated);

        return response()->json([
            'ok' => true,
            'message' => 'Coactivo creado correctamente',
            'data' => $coactivo,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'codigo_expediente_coactivo' => 'sometimes|string|max:100|unique:coactivos,codigo_expediente_coactivo,' . $id . ',id_coactivo',
            'id_expediente' => 'sometimes|integer|exists:expediente,id',
            'ejecutor_coactivo' => 'sometimes|string|max:200',
            'auxiliar_coactivo' => 'nullable|string|max:200',
            'fecha_inicio' => 'nullable|date',
            'monto_deuda' => 'nullable|numeric|min:0',
            'monto_costas' => 'nullable|numeric|min:0',
            'monto_gastos_admin' => 'nullable|numeric|min:0',
            'estado' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
        ]);

        $coactivo = $this->service->update($id, $validated);

        if (!$coactivo) {
            return response()->json(['message' => 'Coactivo no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Coactivo actualizado correctamente',
            'data' => $coactivo,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Coactivo no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Coactivo eliminado correctamente',
        ]);
    }

    /**
     * Vincula un expediente al área coactiva creando expediente coactivo y detalle
     * POST /coactivos/vincular-expediente
     */
    public function vincularExpediente(VincularExpedienteCoactivoRequest $request): JsonResponse
    {
        try {
            $resultado = $this->service->vincularExpedienteCoactivo($request->validated());

            // Obtener el coactivo creado con sus relaciones
            $coactivo = $this->service->getDetailed($resultado['id_coactivo']);

            return response()->json([
                'ok' => true,
                'message' => 'Expediente vinculado a coactivo correctamente',
                'codigo_generado' => $resultado['codigo_generado'],
                'data' => new CoactivoResource($coactivo),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verifica si un expediente ya está vinculado a un coactivo
     * GET /coactivos/verificar-vinculacion/{idExpediente}
     */
    public function verificarVinculacion(int $idExpediente): JsonResponse
    {
        $coactivo = $this->service->getByExpedienteId($idExpediente);

        if ($coactivo) {
            return response()->json([
                'ok' => true,
                'vinculado' => true,
                'data' => new CoactivoListResource($coactivo),
            ]);
        }

        return response()->json([
            'ok' => true,
            'vinculado' => false,
            'data' => null,
        ]);
    }

    /**
     * Obtiene datos del coactivo para prefill del formulario de orden de pago
     * GET /coactivos/{id}/datos-para-orden-pago
     */
    public function getDatosParaOrdenPago(int $id): JsonResponse
    {
        try {
            $datos = $this->service->getDatosParaOrdenPago($id);

            return response()->json([
                'ok' => true,
                'data' => $datos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
