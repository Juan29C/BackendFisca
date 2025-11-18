<?php

namespace App\Services;

use App\Models\TipoDocumentoCoactivo;
use App\Repositories\TipoDocumentoCoactivoRepository;
use Illuminate\Database\Eloquent\Collection;

class TipoDocumentoCoactivoService
{
    protected TipoDocumentoCoactivoRepository $repository;

    public function __construct(TipoDocumentoCoactivoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?TipoDocumentoCoactivo
    {
        return $this->repository->find($id);
    }

    public function getByDescripcion(string $descripcion): ?TipoDocumentoCoactivo
    {
        return $this->repository->findByDescripcion($descripcion);
    }

    public function create(array $data): TipoDocumentoCoactivo
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?TipoDocumentoCoactivo
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
