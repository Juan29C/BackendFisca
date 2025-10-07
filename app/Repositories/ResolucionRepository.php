<?php

namespace App\Repositories;

use App\Models\Resolucion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ResolucionRepository
{

     protected Resolucion $model;

    public function __construct(Resolucion $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Resolucion
    {
        return $this->model->find($id);
    }

    public function create(array $data): Resolucion
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Resolucion
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

    public function numeroResolucionSimple(int $codigo): string
    {
        $rows = DB::select('CALL generar_numero_resolucion_simple(?)', [$codigo]);
        return $rows[0]->numero_resolucion ?? '';
    }

    public function descripcionVisto(?int $id): ?string
    {
        return null;
    }
}

