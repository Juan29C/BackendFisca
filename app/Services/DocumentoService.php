<?php

namespace App\Services;

use App\Repositories\DocumentoRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Documento;

class DocumentoService
{
    protected DocumentoRepository $repository;

    public function __construct(DocumentoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?Documento
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Documento
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?Documento
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
