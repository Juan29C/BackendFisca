<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpedienteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'codigo_expediente' => $this->codigo_expediente,
            'fecha_inicio'      => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
            'estado'            => $this->whenLoaded('estado', fn () => [
                'id'     => $this->estado->id,
                'nombre' => $this->estado->nombre ?? null,
            ]),
            'administrado'      => $this->whenLoaded('administrado', fn () => [
                'id'            => $this->administrado->id,
                'tipo'          => $this->administrado->tipo,
                'dni'           => $this->administrado->dni,
                'ruc'           => $this->administrado->ruc,
                'nombres'       => $this->administrado->nombres,
                'apellidos'     => $this->administrado->apellidos,
                'razon_social'  => $this->administrado->razon_social,
                'domicilio'     => $this->administrado->domicilio,
                'vinculo'       => $this->administrado->vinculo,
            ]),

            'historial' => $this->whenLoaded('historial', fn () =>
                HistorialExpedienteResource::collection($this->historial)
            ),

            'created_at'        => optional($this->created_at)->toISOString(),
            'updated_at'        => optional($this->updated_at)->toISOString(),
        ];
    }
}
