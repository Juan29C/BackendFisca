<?php

namespace App\Services;

use App\Repositories\EstadoExpedienteRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\EstadoExpediente;

class EstadoExpedienteService
{
    protected EstadoExpedienteRepository $repository;

    public function __construct(EstadoExpedienteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?EstadoExpediente
    {
        return $this->repository->find($id);
    }

    public function create(array $data): EstadoExpediente
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?EstadoExpediente
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
