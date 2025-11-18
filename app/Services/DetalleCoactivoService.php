<?php

namespace App\Services;

use App\Models\DetalleCoactivo;
use App\Repositories\DetalleCoactivoRepository;
use Illuminate\Database\Eloquent\Collection;

class DetalleCoactivoService
{
    protected DetalleCoactivoRepository $repository;

    public function __construct(DetalleCoactivoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?DetalleCoactivo
    {
        return $this->repository->find($id);
    }

    public function getByCoactivo(int $idCoactivo): ?DetalleCoactivo
    {
        return $this->repository->findByCoactivo($idCoactivo);
    }

    public function create(array $data): DetalleCoactivo
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?DetalleCoactivo
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function existsForCoactivo(int $idCoactivo): bool
    {
        return $this->repository->existsForCoactivo($idCoactivo);
    }
}
