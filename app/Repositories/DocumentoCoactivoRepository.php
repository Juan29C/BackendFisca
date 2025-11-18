<?php

namespace App\Repositories;

use App\Models\DocumentoCoactivo;
use Illuminate\Database\Eloquent\Collection;

class DocumentoCoactivoRepository
{
    protected DocumentoCoactivo $model;

    public function __construct(DocumentoCoactivo $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?DocumentoCoactivo
    {
        return $this->model->find($id);
    }

    public function create(array $data): DocumentoCoactivo
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?DocumentoCoactivo
    {
        $record = $this->find($id);
        if (!$record) return null;
        $record->update($data);
        return $record;
    }

    public function delete(int $id): bool
    {
        $record = $this->find($id);
        if (!$record) return false;
        return $record->delete();
    }

    public function existsForCoactivoTipo(int $idCoactivo, int $idTipo): bool
    {
        return $this->model->where('id_coactivo', $idCoactivo)
            ->where('id_tipo_doc_coactivo', $idTipo)
            ->exists();
    }

    public function existsWithFileForDoc(int $idCoactivo, int $idTipo, ?string $codigoDoc = null): bool
    {
        return $this->model->where('id_coactivo', $idCoactivo)
            ->when($codigoDoc, fn($q) => $q->where('codigo_doc', $codigoDoc))
            ->where('id_tipo_doc_coactivo', $idTipo)
            ->whereNotNull('ruta')
            ->exists();
    }

    public function findDraftDoc(int $idCoactivo, int $idTipo, ?string $codigoDoc = null): ?DocumentoCoactivo
    {
        return $this->model->where('id_coactivo', $idCoactivo)
            ->when($codigoDoc, fn($q) => $q->where('codigo_doc', $codigoDoc))
            ->where('id_tipo_doc_coactivo', $idTipo)
            ->whereNull('ruta')
            ->latest('id_doc_coactivo')
            ->first();
    }

    public function findByCoactivo(int $idCoactivo): Collection
    {
        return $this->model
            ->with('tipoDocumento')
            ->where('id_coactivo', $idCoactivo)
            ->orderBy('fecha_doc', 'desc')
            ->get();
    }
}
