<?php

namespace App\Services;

use App\Exceptions\TransicionInvalidaException;
use App\Repositories\ExpedienteRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Expediente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Enums\EstadoExpedienteEnum as EE;
use App\Exceptions\ExpedienteDuplicadoException;
use App\Exceptions\RecursoNoCreadoException;
use Illuminate\Database\QueryException;

class ExpedienteService
{
    protected ExpedienteRepository $repository;

    public function __construct(ExpedienteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?Expediente
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Expediente
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?Expediente
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getDetailed(int $id, bool $withHistorial = true, int $historialLimit = 0): ?Expediente
    {
        return $this->repository->findDetailed($id, $withHistorial, $historialLimit);
    }

    public function listForGrid(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForList($filters, $perPage);
    }

    public function crearConStoredProcedure(array $data): Expediente
    {

        $this->assertNumeroDisponible((int) $data['numero_expediente']);
        
        try {
            $expediente = $this->repository->createViaStoredProcedure($data);
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Duplicate entry') && str_contains($msg, 'expediente_codigo_unique')) {
                throw new ExpedienteDuplicadoException('El código de expediente ya existe.');
            }
            if (str_contains($msg, 'Ya existe un expediente con el número')) {
                throw new ExpedienteDuplicadoException($msg);
            }
            throw $e;
        }

        if (!$expediente) {
            throw new RecursoNoCreadoException('No se pudo recuperar el expediente creado.');
        }

        return $expediente;
    }

    private function assertNumeroDisponible(int $numero): void
    {
        $anio = now()->year;

        // Prefijo sin espacios: "EXP-N°{numero}-{anio}"
        $needle = "EXP-N°{$numero}-{$anio}";

        // Quitamos espacios normales y no-breakings de la columna para comparar
        $exists = DB::table('expediente')
            ->whereRaw("
            REPLACE(REPLACE(codigo_expediente, ' ', ''), ' ', '') LIKE CONCAT(?, '%')
        ", [$needle])
            ->exists();

        if ($exists) {
            throw new ExpedienteDuplicadoException(
                "Ya existe un expediente con el número {$numero} en el año {$anio}."
            );
        }
    }



    public function updateBasic(int $id, array $data): ?Expediente
    {
        return DB::transaction(function () use ($id, $data) {
            $exp = Expediente::with(['administrado', 'estado'])->find($id);
            if (!$exp) return null;

            // Si viene bloque administrado
            if (!empty($data['administrado']) && is_array($data['administrado'])) {
                $this->repository->updateAdministrado($exp, $data['administrado']);
                unset($data['administrado']);
            }

            $estadoAnterior = $exp->id_estado ?? null;

            $exp = $this->repository->updateBasic($exp, $data);

            // Si cambió estado, insertamos historial
            if (array_key_exists('id_estado', $data) && $estadoAnterior !== (int)$data['id_estado']) {
                $exp->historial()->create([
                    'id_estado' => $data['id_estado'],
                    'titulo'    => 'Cambio de estado',
                ]);
            }

            return $exp->load(['administrado', 'estado', 'historial.estado']);
        });
    }

    public function deleteExpediente(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $exp = Expediente::with(['documentos'])->find($id);
            if (!$exp) return false;

            $this->repository->deleteWithCascade($exp);
            return true;
        });
    }

    // Funcion para resolver apelación
    public function resolverApelacion(int $expedienteId, bool $huboApelacion): Expediente
    {
        return DB::transaction(function () use ($expedienteId, $huboApelacion) {
            $exp = Expediente::with('estado')->find($expedienteId);
            if (!$exp) abort(404, 'Expediente no encontrado');

            $estadoActual = $exp->id_estado instanceof EE ? $exp->id_estado : EE::from((int)$exp->id_estado);

            if ($estadoActual !== EE::ESPERANDO_APELACION) {
                throw new TransicionInvalidaException(
                    'Solo se puede resolver la apelación cuando el expediente está en "Esperando Apelación".'
                );
            }

            $nuevo = $huboApelacion ? EE::ELEVADO_GERENCIA_SEGURIDAD_CIUD : EE::ELEVADO_COACTIVO;
            $exp->id_estado = $nuevo;
            $exp->save();

            return $exp->load(['administrado', 'estado', 'historial.estado']);
        });
    }

    // Funcion para iniciar reconsideración
    public function iniciarReconsideracion(int $expedienteId): Expediente
    {
        return DB::transaction(function () use ($expedienteId) {
            $exp = Expediente::with('estado')->find($expedienteId);
            if (!$exp) abort(404, 'Expediente no encontrado');

            // Con cast a enum en el modelo:
            $estadoActual = $exp->id_estado instanceof EE ? $exp->id_estado : EE::from((int)$exp->id_estado);

            if ($estadoActual !== EE::EN_PROCESO) {
                throw new TransicionInvalidaException(
                    'Solo se puede iniciar la reconsideración cuando el expediente está en "En Proceso".'
                );
            }

            $exp->id_estado = EE::EVALUANDO_RECONSIDERACION; // o ->value si no usas cast
            $exp->save(); // tu hook booted() registrará historial

            return $exp->load(['administrado', 'estado', 'historial.estado']);
        });
    }
}
