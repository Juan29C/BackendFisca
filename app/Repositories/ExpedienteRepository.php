<?php

namespace App\Repositories;

use App\Models\Expediente;
use Illuminate\Database\Eloquent\Collection;

class ExpedienteRepository
{
    protected Expediente $model;

    public function __construct(Expediente $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
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
}
