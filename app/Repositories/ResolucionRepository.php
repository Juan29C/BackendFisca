<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ResolucionRepository
{
    /** Llama: CALL generar_numero_resolucion_simple(?) y devuelve el string */
    public function numeroResolucionSimple(int $codigo): string
    {
        $rows = DB::select('CALL generar_numero_resolucion_simple(?)', [$codigo]);
        // El SP retorna: SELECT v_resultado AS numero_resolucion;
        return $rows[0]->numero_resolucion ?? '';
    }

    /** Gancho futuro: obtener descripción compuesta por SP */
    public function descripcionVisto(?int $id): ?string
    {
        // Ejemplo futuro:
        // $rows = DB::select('CALL sp_visto_por_expediente(?)', [$id]);
        // return $rows[0]->visto ?? null;
        return null;
    }
}
