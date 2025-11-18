<?php

namespace App\Repositories;

use App\Models\TipoDocumentoCoactivo;
use Illuminate\Database\Eloquent\Collection;

class TipoDocumentoCoactivoRepository
{
    protected TipoDocumentoCoactivo $model;

    public function __construct(TipoDocumentoCoactivo $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?TipoDocumentoCoactivo
    {
        return $this->model->find($id);
    }

    public function findByDescripcion(string $descripcion): ?TipoDocumentoCoactivo
    {
        return $this->model->where('descripcion', $descripcion)->first();
    }

    public function create(array $data): TipoDocumentoCoactivo
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?TipoDocumentoCoactivo
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
