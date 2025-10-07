<?php

namespace App\Services;

use App\Repositories\AdministradoRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Administrado;

class AdministradoService
{
    protected AdministradoRepository $repository;

    public function __construct(AdministradoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?Administrado
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Administrado
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?Administrado
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
