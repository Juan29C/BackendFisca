<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResolucionController extends Controller
{
    public function generarNumeroResolucion()
    {
        try {
            $resultado = collect(DB::select('CALL generar_numero_resolucion_simple(?)', [115]));

            if ($resultado->isNotEmpty()) {
                $numeroResolucion = $resultado[0]->numero_resolucion;

                return response()->json([
                    'numero_resolucion' => $numeroResolucion
                ]);
            } else {
                return response()->json([
                    'error' => 'No se generó ningún número de resolución.'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
