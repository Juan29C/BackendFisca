<?php

namespace App\Repositories;

use App\Models\Expediente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpedienteRepository
{
    protected Expediente $model;

    public function paginateForList(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $q         = $filters['q'] ?? null;
        $estadoId  = $filters['estado_id'] ?? null;

        $query = $this->model->newQuery()
            ->with(['administrado', 'estado']);

        if (!empty($estadoId)) {
            $query->where('id_estado', $estadoId);
        }

        if (!empty($q)) {
            $q = trim((string)$q);
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo_expediente', 'like', "%{$q}%")
                    ->orWhereHas('administrado', function ($a) use ($q) {
                        $a->where('dni', 'like', "%{$q}%")
                            ->orWhere('ruc', 'like', "%{$q}%")
                            ->orWhere('nombres', 'like', "%{$q}%")
                            ->orWhere('apellidos', 'like', "%{$q}%")
                            ->orWhere('razon_social', 'like', "%{$q}%");
                    });
            });
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function __construct(Expediente $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model
            ->with([
                'administrado:id,dni,ruc,tipo,nombres,apellidos,razon_social,domicilio',
                'estado:id,nombre'
            ])
            ->orderByDesc('id')
            ->get(); // <- SIN filtros ni paginate
    }

    public function find(int $id): ?Expediente
    {
        return $this->model->find($id);
    }

    public function create(array $data): Expediente
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Expediente
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


    public function findByCodigo(string $codigo): ?Expediente
    {
        return $this->model
            ->with(['administrado', 'estado'])
            ->where('codigo_expediente', $codigo)
            ->first();
    }

    public function findDetailed(int $id, bool $withHistorial = true, int $historialLimit = 0): ?Expediente
    {
        $with = ['administrado', 'estado'];

        if ($withHistorial) {
            $with['historial'] = function ($q) use ($historialLimit) {
                $q->latest('created_at');
                if ($historialLimit > 0) {
                    $q->limit($historialLimit);
                }
            };
            $with[] = 'historial.estado';
        }

        return $this->model->with($with)->find($id);
    }


    /**
     * Llama al SP para crear expediente (y administrado si corresponde).
     * Luego recalcula el 'codigo_expediente' y lo busca para retornarlo completo.
     *
     * @throws \Throwable si el SP lanza SIGNAL (FKs, duplicados, etc.)
     */
    public function createViaStoredProcedure(array $payload): ?Expediente
    {
        // Llama al SP y captura el SELECT final (id, codigo)
        $row = DB::selectOne(
            'CALL crear_expediente_desde_resolucion(?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $payload['dni'] ?? null,
                $payload['ruc'] ?? null,
                $payload['nombres'] ?? null,
                $payload['apellidos'] ?? null,
                $payload['razon_social'] ?? null,
                $payload['domicilio'] ?? null,
                $payload['vinculo'] ?? null,
                $payload['numero_expediente'],
            ]
        );

        if (!$row) {
            return null;
        }

        return $this->model
            ->with(['administrado', 'estado', 'historial.estado'])
            ->find($row->id);
    }

    public function updateBasic(Expediente $expediente, array $data): Expediente
    {
        $expediente->fill($data);
        $expediente->save();
        return $expediente->refresh();
    }

    public function updateAdministrado(Expediente $expediente, array $adm): void
    {
        $expediente->administrado->fill($adm);
        $expediente->administrado->save();
    }

    public function deleteWithCascade(Expediente $expediente): void
    {
        // Borrar archivos de documentos
        $docs = $expediente->documentos()->get(['ruta']);
        foreach ($docs as $d) {
            if ($d->ruta && Storage::disk('public')->exists($d->ruta)) {
                Storage::disk('public')->delete($d->ruta);
            }
        }

        // Si no hay ON DELETE CASCADE, borra manualmente:
        // $expediente->documentos()->delete();
        // $expediente->historial()->delete();

        $expediente->delete();
    }
}
