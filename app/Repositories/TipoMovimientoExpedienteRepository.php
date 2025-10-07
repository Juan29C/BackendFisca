<?php

namespace App\Repositories;

use App\Models\TipoMovimientoExpediente;
use Illuminate\Database\Eloquent\Collection;

class TipoMovimientoExpedienteRepository
{
    protected TipoMovimientoExpediente $model;

    public function __construct(TipoMovimientoExpediente $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?TipoMovimientoExpediente
    {
        return $this->model->find($id);
    }

    public function create(array $data): TipoMovimientoExpediente
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?TipoMovimientoExpediente
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
