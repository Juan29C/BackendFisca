<?php

namespace App\Services;

use App\Models\Coactivo;
use App\Repositories\CoactivoRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CoactivoService
{
    protected CoactivoRepository $repository;

    public function __construct(CoactivoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForList($filters, $perPage);
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?Coactivo
    {
        return $this->repository->find($id);
    }

    public function getDetailed(int $id): ?Coactivo
    {
        return $this->repository->findDetailed($id);
    }

    public function getByCodigo(string $codigo): ?Coactivo
    {
        return $this->repository->findByCodigo($codigo);
    }

    public function create(array $data): Coactivo
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?Coactivo
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function existsByCodigo(string $codigo): bool
    {
        return $this->repository->existsByCodigo($codigo);
    }

    /**
     * Crea un expediente coactivo vinculado a un expediente usando stored procedure
     */
    public function vincularExpedienteCoactivo(array $data): array
    {
        $idExpediente = $data['id_expediente'];
        $year = now()->year;
        $correlativo = $data['correlativo'];

        // Validar que el expediente no esté ya vinculado a un coactivo
        if ($this->repository->existsByExpedienteId($idExpediente)) {
            throw new \Exception(
                "El expediente con ID {$idExpediente} ya está vinculado a un expediente coactivo."
            );
        }

        // Validar que no exista el correlativo para el año actual
        if ($this->repository->existsByCorrelativoAndYear($correlativo, $year)) {
            throw new \Exception(
                "Ya existe un expediente coactivo con el correlativo {$correlativo} para el año {$year}. " .
                "El código " . str_pad($correlativo, 4, '0', STR_PAD_LEFT) . "-N-{$year}-MDNCH-GEC ya está en uso."
            );
        }

        return $this->repository->crearExpedienteCoactivoConSP($data);
    }

    /**
     * Obtiene todos los coactivos con expediente y administrado para la lista
     */
    public function getAllWithRelations(): Collection
    {
        return $this->repository->getAllForList();
    }

    /**
     * Obtiene un coactivo por ID de expediente
     */
    public function getByExpedienteId(int $idExpediente): ?Coactivo
    {
        return $this->repository->findByExpedienteId($idExpediente);
    }
}
