<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ResolucionRepository
{
    public function numeroResolucionSimple(int $codigo): string
    {
        $rows = DB::select('CALL generar_numero_resolucion_simple(?)', [$codigo]);
        return $rows[0]->numero_resolucion ?? '';
    }

    public function descripcionVisto(?int $id): ?string
    {
        return null;
    }
}
