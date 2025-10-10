<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HistorialExpedienteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'id_estado'  => $this->id_estado,
            'estado'     => $this->whenLoaded('estado', fn () => [
                'id'     => $this->estado->id,
                'nombre' => $this->estado->nombre ?? null,
            ]),
            'titulo'     => $this->titulo,
        ];
    }
}
