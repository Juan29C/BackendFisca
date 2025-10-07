<?php

namespace App\Repositories;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Collection;

class DocumentoRepository
{
    protected Documento $model;

    public function __construct(Documento $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Documento
    {
        return $this->model->find($id);
    }

    public function create(array $data): Documento
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Documento
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
