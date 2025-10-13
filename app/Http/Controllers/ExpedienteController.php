<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpedienteRequest;
use App\Http\Requests\UploadExpedienteDocumentosRequest;
use App\Http\Resources\ExpedienteListResource;
use App\Http\Resources\ExpedienteResource;
use App\Models\Documento;
use App\Models\Expediente;
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

    // Upload de documentos
    public function uploadDocumentos(UploadExpedienteDocumentosRequest $request, int $id): JsonResponse
    {
        // 1) Buscar expediente con su administrado (para carpeta por DNI/RUC)
        $expediente = Expediente::with('administrado')->find($id);
        if (!$expediente) {
            return response()->json(['ok' => false, 'message' => 'Expediente no encontrado'], 404);
        }

        $adm = $expediente->administrado;
        $slugPersona = null;

        if ($adm) {
            // Carpeta por DNI o RUC según tipo
            if ($adm->tipo === 'juridica' && !empty($adm->ruc)) {
                $slugPersona = $adm->ruc;
            } elseif ($adm->tipo === 'natural' && !empty($adm->dni)) {
                $slugPersona = $adm->dni;
            }
        }
        if (!$slugPersona) {
            // Fallback si no hay doc de identidad
            $slugPersona = 'expediente_' . $expediente->id;
        }

        // Carpeta destino: storage/app/public/expedientes/{dni|ruc}/
        $baseFolder = "expedientes/{$slugPersona}";

        $files = $request->file('files', []); // array asociativo: [ id_tipo => UploadedFile ]
        if (empty($files)) {
            return response()->json([
                'ok' => true,
                'message' => 'No se enviaron archivos. Nada que subir.',
                'uploaded' => []
            ]);
        }

        $uploaded = [];

        foreach ($files as $idTipo => $file) {

            if (!is_numeric($idTipo)) {
                continue;
            }
            $path = $file->store($baseFolder, 'public');
            $url  = Storage::url($path);

            $doc = Documento::create([
                'id_expediente' => $expediente->id,
                'id_tipo'       => (int)$idTipo,
                'codigo_doc'    => null,
                'fecha_doc'     => null,
                'descripcion'   => null,
                'ruta'          => $url,
            ]);

            $uploaded[] = [
                'id'           => $doc->id,
                'id_tipo'      => (int)$idTipo,
                'nombre'       => $file->getClientOriginalName(),
                'ruta'         => $url,
            ];
        }

        return response()->json([
            'ok'       => true,
            'message'  => 'Documentos subidos correctamente.',
            'uploaded' => $uploaded
        ], 201);
    }
}
