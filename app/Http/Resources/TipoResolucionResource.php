<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TipoResolucionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,

            'resoluciones'=> $this->whenLoaded('resoluciones', fn () =>
                $this->resoluciones->pluck('id')
            ),

            'created_at'  => optional($this->created_at)->toISOString(),
            'updated_at'  => optional($this->updated_at)->toISOString(),
        ];
    }
}
