<?php

namespace App\Services;

use App\Models\EntidadBancaria;
use App\Repositories\EntidadBancariaRepository;
use Illuminate\Database\Eloquent\Collection;

class EntidadBancariaService
{
    protected EntidadBancariaRepository $repository;

    public function __construct(EntidadBancariaRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?EntidadBancaria
    {
        return $this->repository->find($id);
    }

    public function getByRuc(string $ruc): ?EntidadBancaria
    {
        return $this->repository->findByRuc($ruc);
    }

    public function create(array $data): EntidadBancaria
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?EntidadBancaria
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
