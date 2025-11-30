<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoactivoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_coactivo' => $this->id_coactivo,
            'codigo_expediente_coactivo' => $this->codigo_expediente_coactivo,
            'id_expediente' => $this->id_expediente,
            'ejecutor_coactivo' => $this->ejecutor_coactivo,
            'auxiliar_coactivo' => $this->auxiliar_coactivo,
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'monto_deuda' => $this->monto_deuda,
            'monto_costas' => $this->monto_costas,
            'monto_gastos_admin' => $this->monto_gastos_admin,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relaciones (si están cargadas)
            'expediente' => $this->whenLoaded('expediente', function () {
                return [
                    'id' => $this->expediente->id,
                    'numero_expediente' => $this->expediente->numero_expediente,
                    'tipo_infraccion' => $this->expediente->tipo_infraccion,
                ];
            }),
            
            'detalles' => $this->whenLoaded('detalles', function () {
                return $this->detalles->map(function ($detalle) {
                    return [
                        'id_detalle_coactivo' => $detalle->id_detalle_coactivo,
                        'res_sancion_codigo' => $detalle->res_sancion_codigo,
                        'res_sancion_fecha' => $detalle->res_sancion_fecha?->format('Y-m-d'),
                        'res_consentida_codigo' => $detalle->res_consentida_codigo,
                        'res_consentida_fecha' => $detalle->res_consentida_fecha?->format('Y-m-d'),
                        'papeleta_codigo' => $detalle->papeleta_codigo,
                        'papeleta_fecha' => $detalle->papeleta_fecha?->format('Y-m-d'),
                        'codigo_infraccion' => $detalle->codigo_infraccion,
                        'descripcion_infraccion' => $detalle->descripcion_infraccion,
                    ];
                });
            }),
        ];
    }
}
