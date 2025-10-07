<?php

namespace App\Repositories;

use App\Models\EstadoExpediente;
use Illuminate\Database\Eloquent\Collection;

class EstadoExpedienteRepository
{
    protected EstadoExpediente $model;

    public function __construct(EstadoExpediente $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?EstadoExpediente
    {
        return $this->model->find($id);
    }

    public function create(array $data): EstadoExpediente
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?EstadoExpediente
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
