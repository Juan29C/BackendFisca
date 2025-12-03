<?php

namespace App\Repositories;

use App\Models\Resolucion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ResolucionRepository
{

    protected Resolucion $model;

    public function __construct(Resolucion $model)
    {
        $this->model = $model;
    }

    public function listAll(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Resolucion
    {
        return $this->model->find($id);
    }

    public function create(array $data): Resolucion
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Resolucion
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

    public function numeroResolucionSimple(int $codigo): string
    {
        $rows = DB::select('CALL generar_numero_resolucion_simple(?)', [$codigo]);
        return $rows[0]->numero_resolucion ?? '';
    }

    public function descripcionVisto(?int $id): ?string
    {
        return null;
    }

    // Crear una resolución desde un expediente
    public function numeroResolucionSubgerencial(int $codigo): string
    {
        $row = DB::selectOne('SELECT fn_generar_numero_resolucion_subgerencial(?) AS numero', [$codigo]);
        return $row?->numero ?? '';
    }

    public function createViaStoredProcedure(
        int $idExpediente,
        int $codigoResolucion,
        int $idTipoResolucion,
        array $documentos = []
    ): ?Resolucion {
        // 1) CSVs (si no hay docs, pasamos NULL para que el WHILE no itere)
        if (!empty($documentos)) {
            $codigos = implode(',', array_map(fn($d) => $d['codigo_doc'], $documentos));
            $fechas  = implode(',', array_map(fn($d) => $d['fecha_doc'],  $documentos));
            $tipos   = implode(',', array_map(fn($d) => $d['id_tipo'],     $documentos));
        } else {
            $codigos = $fechas = $tipos = null;
        }

        // 2) Ejecutar SP
        DB::statement('CALL crear_resolucion_con_documentos(?, ?, ?, ?, ?, ?)', [
            $idExpediente,
            $codigoResolucion,
            $idTipoResolucion,
            $codigos,
            $fechas,
            $tipos,
        ]);

        // 3) Calcular el numero que generó el SP para buscar la resolución
        $numero = $this->numeroResolucionSubgerencial($codigoResolucion);

        // 4) Buscar la resolución recién creada por expediente y numero
        $res = $this->model
            ->with(['tipoResolucion', 'documentos.tipoDocumento'])
            ->where('id_expediente', $idExpediente)
            ->where('numero', $numero)
            ->latest('id')
            ->first();

        if (!$res) {
            \Illuminate\Support\Facades\Log::error('Resolución no encontrada después del SP', [
                'id_expediente' => $idExpediente,
                'numero_buscado' => $numero,
                'codigo_resolucion' => $codigoResolucion,
                'id_tipo_resolucion' => $idTipoResolucion,
            ]);
        }

        return $res;
    }
}
