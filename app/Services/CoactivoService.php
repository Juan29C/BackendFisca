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

    /**
     * Obtiene datos del coactivo para prefill del formulario de orden de pago
     */
    public function getDatosParaOrdenPago(int $id): array
    {
        $coactivo = $this->repository->find($id);
        
        if (!$coactivo) {
            throw new \Exception("Expediente coactivo no encontrado");
        }

        $coactivo->load(['expediente.administrado', 'detalles']);

        $administrado = $coactivo->expediente->administrado;
        if (!$administrado) {
            throw new \Exception("Administrado no encontrado en el expediente");
        }

        // Calcular monto total
        $montoDeuda = $coactivo->monto_deuda ?? 0;
        $montoCostas = $coactivo->monto_costas ?? 0;
        $montoGastosAdmin = $coactivo->monto_gastos_admin ?? 0;
        $montoTotal = $montoDeuda + $montoCostas + $montoGastosAdmin;

        // Obtener primer detalle para cod_sancion
        $detalle = $coactivo->detalles->first();
        $codSancion = $detalle?->res_sancion_codigo ?? '';

        // Nombre completo
        $nombreCompleto = trim(($administrado->nombres ?? '') . ' ' . ($administrado->apellidos ?? '')) ?: ($administrado->razon_social ?? '');

        return [
            'id_coactivo' => $coactivo->id_coactivo,
            'codigo_expediente_coactivo' => $coactivo->codigo_expediente_coactivo ?? '',
            'nombre_completo' => $nombreCompleto,
            'domicilio' => $administrado->domicilio ?? '',
            'documento' => $administrado->dni ?: ($administrado->ruc ?? ''),
            'cod_sancion' => $codSancion,
            'monto_total' => $montoTotal,
            'monto_deuda' => $montoDeuda,
            'monto_costas' => $montoCostas,
            'monto_gastos_admin' => $montoGastosAdmin,
        ];
    }

    /**
     * Busca expedientes coactivos por documento (DNI/RUC) del administrado
     */
    public function buscarPorDocumentoAdministrado(string $documento): array
    {
        return $this->repository->findByAdministradoDocumento($documento);
    }
}
