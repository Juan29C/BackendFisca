<?php

namespace App\Repositories;

use App\Models\Coactivo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CoactivoRepository
{
    protected Coactivo $model;

    public function __construct(Coactivo $model)
    {
        $this->model = $model;
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
                'expediente.administrado',
                'expediente.estado',
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
}
