<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EntidadBancariaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id_entidad_bancaria' => $this->id_entidad_bancaria,
            'nombre' => $this->nombre,
            'ruc' => $this->ruc,
            'direccion' => $this->direccion,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
