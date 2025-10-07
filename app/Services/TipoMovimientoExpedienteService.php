<?php

namespace App\Services;

use App\Repositories\TipoMovimientoExpedienteRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\TipoMovimientoExpediente;

class TipoMovimientoExpedienteService
{
    protected TipoMovimientoExpedienteRepository $repository;

    public function __construct(TipoMovimientoExpedienteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?TipoMovimientoExpediente
    {
        return $this->repository->find($id);
    }

    public function create(array $data): TipoMovimientoExpediente
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?TipoMovimientoExpediente
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
