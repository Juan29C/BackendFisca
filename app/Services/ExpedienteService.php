<?php

namespace App\Services;

use App\Repositories\ExpedienteRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Expediente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
