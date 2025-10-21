<?php

namespace App\Repositories;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Collection;

class DocumentoRepository
{
    protected Documento $model;

    public function __construct(Documento $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Documento
    {
        return $this->model->find($id);
    }

    public function create(array $data): Documento
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Documento
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


    // Verificar si existe un documento con el mismo codigo de documento
    public function existsForExpedienteTipo(int $expedienteId, int $tipoId): bool
    {
        return $this->model->where('id_expediente', $expedienteId)
            ->where('id_tipo', $tipoId)
            ->exists();
    }

    // Verificar si ya hay archivo subido para ese documento
    public function existsWithFileForDoc(int $expedienteId, int $tipoId, ?string $codigoDoc = null): bool
    {
        return $this->model->where('id_expediente', $expedienteId)
            ->when($codigoDoc, fn($q) => $q->where('codigo_doc', $codigoDoc))
            ->where('id_tipo', $tipoId)
            ->whereNotNull('ruta')
            ->exists();
    }

    // Buscar un documento “borrador” (sin archivo aún) para reutilizarlo
    public function findDraftDoc(int $expedienteId, int $tipoId, ?string $codigoDoc = null): ?Documento
    {
        return $this->model->where('id_expediente', $expedienteId)
            ->when($codigoDoc, fn($q) => $q->where('codigo_doc', $codigoDoc))
            ->where('id_tipo', $tipoId)
            ->whereNull('ruta')
            ->latest('id')
            ->first();
    }
}
