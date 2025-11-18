<?php

namespace App\Services;

use App\Models\EstadoCoactivo;
use App\Repositories\EstadoCoactivoRepository;
use Illuminate\Database\Eloquent\Collection;

class EstadoCoactivoService
{
    protected EstadoCoactivoRepository $repository;

    public function __construct(EstadoCoactivoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?EstadoCoactivo
    {
        return $this->repository->find($id);
    }

    public function getByNombre(string $nombre): ?EstadoCoactivo
    {
        return $this->repository->findByNombre($nombre);
    }

    public function create(array $data): EstadoCoactivo
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?EstadoCoactivo
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
