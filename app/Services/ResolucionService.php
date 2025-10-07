<?php

namespace App\Services;

use App\Repositories\ResolucionRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Resolucion;

class ResolucionService
{
    protected ResolucionRepository $repository;

    public function __construct(ResolucionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?Resolucion
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Resolucion
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?Resolucion
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
