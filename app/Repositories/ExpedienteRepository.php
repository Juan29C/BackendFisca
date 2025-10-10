<?php

namespace App\Repositories;

use App\Models\Expediente;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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


    public function findByCodigo(string $codigo): ?Expediente
    {
        return $this->model
            ->with(['administrado', 'estado'])
            ->where('codigo_expediente', $codigo)
            ->first();
    }


    /**
     * Llama al SP para crear expediente (y administrado si corresponde).
     * Luego recalcula el 'codigo_expediente' y lo busca para retornarlo completo.
     *
     * @throws \Throwable si el SP lanza SIGNAL (FKs, duplicados, etc.)
     */
    public function createViaStoredProcedure(array $payload): ?Expediente
    {
        // Llama al SP y captura el SELECT final (id, codigo)
        $row = DB::selectOne(
            'CALL crear_expediente_desde_resolucion(?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $payload['dni'] ?? null,
                $payload['ruc'] ?? null,
                $payload['nombres'] ?? null,
                $payload['apellidos'] ?? null,
                $payload['razon_social'] ?? null,
                $payload['domicilio'] ?? null,
                $payload['vinculo'] ?? null,
                $payload['numero_expediente'],
            ]
        );

        if (!$row) {
            return null;
        }

        return $this->model
            ->with(['administrado', 'estado', 'historial.estado'])
            ->find($row->id);
    }
}
