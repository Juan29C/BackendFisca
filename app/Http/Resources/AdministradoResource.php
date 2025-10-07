<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdministradoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'tipo'            => $this->tipo,
            'dni'             => $this->dni,
            'ruc'             => $this->ruc,
            'nombre_completo' => $this->nombre_completo,
            'razon_social'    => $this->razon_social,
            'domicilio'       => $this->domicilio,
            'vinculo'         => $this->vinculo,

            // Relaciones (opcionales, solo si fueron eager-loaded)
            'expedientes'     => $this->whenLoaded('expedientes', fn () =>
                $this->expedientes->pluck('id')
            ),

            // Timestamps si existen en tu tabla
            'created_at'      => optional($this->created_at)->toISOString(),
            'updated_at'      => optional($this->updated_at)->toISOString(),
        ];
    }
}
