<?php

namespace App\Repositories;

use App\Models\Administrado;
use Illuminate\Database\Eloquent\Collection;

class AdministradoRepository
{
    protected Administrado $model;

    public function __construct(Administrado $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Administrado
    {
        return $this->model->find($id);
    }

    public function create(array $data): Administrado
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Administrado
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
