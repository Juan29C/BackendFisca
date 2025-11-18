<?php

namespace App\Repositories;

use App\Models\DetalleCoactivo;
use Illuminate\Database\Eloquent\Collection;

class DetalleCoactivoRepository
{
    protected DetalleCoactivo $model;

    public function __construct(DetalleCoactivo $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?DetalleCoactivo
    {
        return $this->model->find($id);
    }

    public function findByCoactivo(int $idCoactivo): ?DetalleCoactivo
    {
        return $this->model->where('id_coactivo', $idCoactivo)->first();
    }

    public function create(array $data): DetalleCoactivo
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?DetalleCoactivo
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

    public function existsForCoactivo(int $idCoactivo): bool
    {
        return $this->model->where('id_coactivo', $idCoactivo)->exists();
    }
}
