<?php

namespace App\Services;

use App\Repositories\TipoResolucionRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\TipoResolucion;

class TipoResolucionService
{
    protected TipoResolucionRepository $repository;

    public function __construct(TipoResolucionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?TipoResolucion
    {
        return $this->repository->find($id);
    }

    public function create(array $data): TipoResolucion
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?TipoResolucion
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
