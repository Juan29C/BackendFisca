<?php

namespace App\Repositories;

use App\Models\Coactivo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CoactivoRepository
{
    protected Coactivo $model;

    public function __construct(Coactivo $model)
    {
        $this->model = $model;
    }

    /**
     * Retorna contadores para el dashboard de coactivo
     * - expedientes: total de registros en tabla expediente
     * - coactivos: total de registros en tabla coactivos
     * - finalizados: coactivos con estado = 'Archivado'
     */
    public function getDashboardCounts(): array
    {
        $expedientes = DB::table('expediente')->count();
        $coactivos = $this->model->count();
        $finalizados = $this->model->whereRaw("LOWER(TRIM(estado)) = 'archivado'")->count();

        return [
            'expedientes' => $expedientes,
            'coactivos' => $coactivos,
            'finalizados' => $finalizados,
        ];
    }

    public function paginateForList(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $q      = $filters['q'] ?? null;
        $estado = $filters['estado'] ?? null;

        $query = $this->model->newQuery()
            ->with(['expediente.administrado', 'estadoCoactivo']);

        if (!empty($estado)) {
            $query->where('estado', $estado);
        }

        if (!empty($q)) {
            $q = trim((string)$q);
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo_expediente_coactivo', 'like', "%{$q}%")
                    ->orWhereHas('expediente', function ($e) use ($q) {
                        $e->where('codigo_expediente', 'like', "%{$q}%")
                            ->orWhereHas('administrado', function ($a) use ($q) {
                                $a->where('dni', 'like', "%{$q}%")
                                    ->orWhere('ruc', 'like', "%{$q}%")
                                    ->orWhere('nombres', 'like', "%{$q}%")
                                    ->orWhere('apellidos', 'like', "%{$q}%")
                                    ->orWhere('razon_social', 'like', "%{$q}%");
                            });
                    });
            });
        }

        return $query->orderByDesc('id_coactivo')->paginate($perPage)->withQueryString();
    }

    public function listAll(): Collection
    {
        return $this->model
            ->with([
                'expediente.administrado:id,dni,ruc,tipo,nombres,apellidos,razon_social,domicilio',
                'expediente:id,codigo_expediente,id_administrado',
                'estadoCoactivo:id,nombre'
            ])
            ->orderByDesc('id_coactivo')
            ->get();
    }

    public function find(int $id): ?Coactivo
    {
        return $this->model->find($id);
    }

    public function create(array $data): Coactivo
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Coactivo
    {
        $record = $this->find($id);
        if (!$record) return null;
        $record->update($data);
        return $record;
    }

    public function delete(int $id): bool
    {
        $record = $this->find($id);
        if (!$record) return false;
        return $record->delete();
    }

    public function findByCodigo(string $codigo): ?Coactivo
    {
        return $this->model
            ->with(['expediente.administrado', 'estadoCoactivo'])
            ->where('codigo_expediente_coactivo', $codigo)
            ->first();
    }

    public function findDetailed(int $id): ?Coactivo
    {
        return $this->model
            ->with([
                'expediente' => function ($query) {
                    $query->with(['administrado', 'estado']);
                },
                'estadoCoactivo',
                'detalles',
                'documentos.tipoDocumento'
            ])
            ->find($id);
    }

    public function existsByCodigo(string $codigo): bool
    {
        return $this->model->where('codigo_expediente_coactivo', $codigo)->exists();
    }

    /**
     * Crea expediente coactivo y detalle usando stored procedure
     */
    public function crearExpedienteCoactivoConSP(array $data): array
    {
        $result = DB::select('CALL crear_expediente_y_detalle_coactivo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $data['id_expediente'],
            $data['correlativo'],
            $data['ejecutor_coactivo'],
            $data['auxiliar_coactivo'] ?? null,
            $data['observaciones'] ?? null,
            $data['res_sancion_codigo'] ?? null,
            $data['res_sancion_fecha'] ?? null,
            $data['res_consentida_codigo'] ?? null,
            $data['res_consentida_fecha'] ?? null,
            $data['papeleta_codigo'] ?? null,
            $data['papeleta_fecha'] ?? null,
            $data['codigo_infraccion'] ?? null,
            $data['descripcion_infraccion'] ?? null,
            $data['monto_deuda'],
            $data['monto_costas'] ?? 0,
            $data['monto_gastos_admin'] ?? 0,
        ]);

        // Si se envió el tipo de papeleta en el request, actualizar el detalle creado por el SP
        // (el SP no recibe este campo). Intentamos ubicar el detalle creado por id_coactivo
        // y por el código de papeleta si se proporcionó.
        $idCoactivo = $result[0]->id_coactivo;

        if (!empty($data['tipo_papeleta'])) {
            $updateQuery = DB::table('detalle_coactivo')
                ->where('id_coactivo', $idCoactivo);

            if (!empty($data['papeleta_codigo'])) {
                $updateQuery->where('papeleta_codigo', $data['papeleta_codigo']);
            }

            $updateQuery->update([
                'tipo_papeleta' => $data['tipo_papeleta'],
                'updated_at' => now(),
            ]);
        }

        return [
            'id_coactivo' => $idCoactivo,
            'codigo_generado' => $result[0]->codigo_generado,
        ];
    }

    /**
     * Obtiene todos los coactivos con expediente y administrado para mostrar en lista
     */
    public function getAllForList(): Collection
    {
        return $this->model
            ->with([
                'expediente:id,codigo_expediente,id_administrado',
                'expediente.administrado:id,dni,ruc,tipo,nombres,apellidos,razon_social,domicilio'
            ])
            ->orderByDesc('id_coactivo')
            ->get();
    }

    /**
     * Verifica si existe un coactivo con el correlativo y año especificado
     * Busca por patrón: {correlativo}-N-{año}-MDNCH-GEC
     */
    public function existsByCorrelativoAndYear(int $correlativo, int $year): bool
    {
        $patron = str_pad($correlativo, 4, '0', STR_PAD_LEFT) . '-N-' . $year . '-MDNCH-GEC';
        return $this->model->where('codigo_expediente_coactivo', $patron)->exists();
    }

    /**
     * Verifica si un expediente ya está vinculado a un coactivo
     */
    public function existsByExpedienteId(int $idExpediente): bool
    {
        return $this->model->where('id_expediente', $idExpediente)->exists();
    }

    /**
     * Busca un coactivo por ID de expediente
     */
    public function findByExpedienteId(int $idExpediente): ?Coactivo
    {
        return $this->model
            ->with([
                'expediente.administrado',
                'expediente.estado',
                'estadoCoactivo'
            ])
            ->where('id_expediente', $idExpediente)
            ->first();
    }

    /**
     * Busca expedientes coactivos por documento (DNI/RUC) del administrado
     * Retorna array simplificado con info básica para selección
     */
    public function findByAdministradoDocumento(string $documento): array
    {
        $coactivos = $this->model
            ->select([
                'coactivos.id_coactivo',
                'coactivos.codigo_expediente_coactivo',
                'coactivos.estado',
                'coactivos.monto_deuda',
                'coactivos.monto_costas',
                'coactivos.monto_gastos_admin',
                'coactivos.fecha_inicio'
            ])
            ->join('expediente', 'coactivos.id_expediente', '=', 'expediente.id')
            ->join('administrado', 'expediente.id_administrado', '=', 'administrado.id')
            ->where(function ($query) use ($documento) {
                $query->where('administrado.dni', $documento)
                      ->orWhere('administrado.ruc', $documento);
            })
            ->orderByDesc('coactivos.id_coactivo')
            ->get();

        return $coactivos->map(function ($coactivo) {
            $montoTotal = ($coactivo->monto_deuda ?? 0) 
                        + ($coactivo->monto_costas ?? 0) 
                        + ($coactivo->monto_gastos_admin ?? 0);

            return [
                'id_coactivo' => $coactivo->id_coactivo,
                'codigo_expediente_coactivo' => $coactivo->codigo_expediente_coactivo,
                'estado' => $coactivo->estado ?? 'Activo',
                'monto_total' => $montoTotal,
                'fecha_inicio' => $coactivo->fecha_inicio,
            ];
        })->toArray();
    }
}
