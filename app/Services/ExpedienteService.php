<?php

namespace App\Services;

use App\Repositories\ExpedienteRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Expediente;

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

    public function crearConStoredProcedure(array $data): ?Expediente
    {
        // Aquí podrías envolver en try/catch para mapear errores del SP
        return $this->repository->createViaStoredProcedure($data);
    }
}
