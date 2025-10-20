<?php

namespace App\Services;

use App\Repositories\ExpedienteRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Expediente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpedienteService
{
    protected ExpedienteRepository $repository;

    public function __construct(ExpedienteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?Expediente
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Expediente
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?Expediente
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getDetailed(int $id, bool $withHistorial = true, int $historialLimit = 0): ?Expediente
    {
        return $this->repository->findDetailed($id, $withHistorial, $historialLimit);
    }

    public function listForGrid(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForList($filters, $perPage);
    }

    public function crearConStoredProcedure(array $data): ?Expediente
    {
        return $this->repository->createViaStoredProcedure($data);
    }

    public function updateBasic(int $id, array $data): ?Expediente
    {
        return DB::transaction(function () use ($id, $data) {
            $exp = Expediente::with(['administrado', 'estado'])->find($id);
            if (!$exp) return null;

            // Si viene bloque administrado
            if (!empty($data['administrado']) && is_array($data['administrado'])) {
                $this->repository->updateAdministrado($exp, $data['administrado']);
                unset($data['administrado']);
            }

            $estadoAnterior = $exp->id_estado ?? null;

            $exp = $this->repository->updateBasic($exp, $data);

            // Si cambió estado, insertamos historial
            if (array_key_exists('id_estado', $data) && $estadoAnterior !== (int)$data['id_estado']) {
                $exp->historial()->create([
                    'id_estado' => $data['id_estado'],
                    'titulo'    => 'Cambio de estado',
                ]);
            }

            return $exp->load(['administrado', 'estado', 'historial.estado']);
        });
    }

    public function deleteExpediente(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $exp = Expediente::with(['documentos'])->find($id);
            if (!$exp) return false;

            $this->repository->deleteWithCascade($exp);
            return true;
        });
    }
}
