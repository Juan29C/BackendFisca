<?php

namespace App\Services;

use App\Models\Expediente;
use App\Repositories\ResolucionRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Resolucion;
use App\Models\TipoResolucion;
use App\Models\TiposDocumento;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ResolucionService
{
    protected ResolucionRepository $repository;

    public function __construct(ResolucionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?Resolucion
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Resolucion
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?Resolucion
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    // Crear una resolución desde un expediente
    public function crearDesdeSpFromRequest(int $idExpediente, array $payload): Resolucion
    {
        return DB::transaction(function () use ($idExpediente, $payload) {
            $expediente = Expediente::find($idExpediente);
            if (!$expediente) {
                throw new \DomainException('Expediente no encontrado');
            }

            $tipoId = (int) ($payload['id_tipo_resolucion'] ?? 0);
            $tipo = TipoResolucion::find($tipoId);
            if (!$tipo) {
                throw new \DomainException('Tipo de resolución no encontrado');
            }

            $documentos = array_values(array_filter($payload['documentos'] ?? [], function ($d) {
                return isset($d['codigo_doc'], $d['fecha_doc'], $d['id_tipo'])
                    && $d['codigo_doc'] !== ''
                    && $d['fecha_doc']  !== ''
                    && !empty($d['id_tipo']);
            }));

            if (!empty($documentos)) {
                $idsPorIndice = [];
                foreach ($documentos as $idx => $doc) {
                    if (isset($doc['id_tipo'])) {
                        $idsPorIndice[$idx] = (int) $doc['id_tipo'];
                    }
                }

                $idsTipo = array_values(array_unique(array_values($idsPorIndice)));

                $idsValidos = TiposDocumento::whereIn('id', $idsTipo)->pluck('id')->all();

                $idsInvalidos = array_values(array_diff($idsTipo, $idsValidos));

                if (!empty($idsInvalidos)) {
                    $errors = [];

                    foreach ($idsPorIndice as $idx => $idTipoEnviado) {
                        if (in_array($idTipoEnviado, $idsInvalidos, true)) {
                            $errors["documentos.$idx.id_tipo"] = [
                                "El tipo de documento (id={$idTipoEnviado}) no existe."
                            ];
                        }
                    }

                    $errors['documentos'] = [
                        'Tipos de documento inexistentes: ' . implode(', ', $idsInvalidos)
                    ];

                    throw ValidationException::withMessages($errors);
                }
            }

            $res = $this->repository->createViaStoredProcedure(
                $idExpediente,
                (int) $payload['codigo_resolucion'],
                $tipoId,
                $documentos
            );

            if (!$res) {
                throw new RuntimeException('No se pudo recuperar la resolución creada.');
            }

            return $res;
        });
    }
}
