<?php

namespace App\Repositories;

use App\Models\TiposDocumento;
use Illuminate\Database\Eloquent\Collection;

class TiposDocumentoRepository
{
    protected TiposDocumento $model;

    public function __construct(TiposDocumento $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?TiposDocumento
    {
        return $this->model->find($id);
    }

    public function create(array $data): TiposDocumento
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?TiposDocumento
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
