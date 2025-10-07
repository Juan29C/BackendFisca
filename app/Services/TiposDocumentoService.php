<?php

namespace App\Services;

use App\Repositories\TiposDocumentoRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\TiposDocumento;

class TiposDocumentoService
{
    protected TiposDocumentoRepository $repository;

    public function __construct(TiposDocumentoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?TiposDocumento
    {
        return $this->repository->find($id);
    }

    public function create(array $data): TiposDocumento
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?TiposDocumento
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
