<?php

namespace App\Repositories;

use App\Models\EstadoCoactivo;
use Illuminate\Database\Eloquent\Collection;

class EstadoCoactivoRepository
{
    protected EstadoCoactivo $model;

    public function __construct(EstadoCoactivo $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?EstadoCoactivo
    {
        return $this->model->find($id);
    }

    public function findByNombre(string $nombre): ?EstadoCoactivo
    {
        return $this->model->where('nombre', $nombre)->first();
    }

    public function create(array $data): EstadoCoactivo
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?EstadoCoactivo
    {
        $record = $this->find($id);
        if (!$record) {
            return null;
        }
        $record->update($data);
        return $record;
    }

    public function delete(int $id): bool
    {
        $record = $this->find($id);
        if (!$record) {
            return false;
        }
        return $record->delete();
    }
}
