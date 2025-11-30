<?php

namespace App\Repositories;

use App\Models\EntidadBancaria;
use Illuminate\Database\Eloquent\Collection;

class EntidadBancariaRepository
{
    protected EntidadBancaria $model;

    public function __construct(EntidadBancaria $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?EntidadBancaria
    {
        return $this->model->find($id);
    }

    public function findByRuc(string $ruc): ?EntidadBancaria
    {
        return $this->model->where('ruc', $ruc)->first();
    }

    public function create(array $data): EntidadBancaria
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?EntidadBancaria
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
