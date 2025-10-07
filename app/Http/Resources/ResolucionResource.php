<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResolucionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'id_expediente'     => $this->id_expediente,
            'numero'            => $this->numero,
            'fecha'             => optional($this->fecha)->toDateString(),
            'lugar_emision'     => $this->lugar_emision,
            'texto'             => $this->texto,
            'id_tipo_resolucion'=> $this->id_tipo_resolucion,

            'expediente'        => $this->whenLoaded('expediente', fn () => [
                'id' => $this->expediente->id,
            ]),
            'tipo_resolucion'   => $this->whenLoaded('tipoResolucion', fn () => [
                'id'          => $this->tipoResolucion->id,
                'nombre'      => $this->tipoResolucion->nombre,
                'descripcion' => $this->tipoResolucion->descripcion,
            ]),
            'documentos'        => $this->whenLoaded('documentos', fn () =>
                $this->documentos->pluck('id')
            ),

            'created_at'        => optional($this->created_at)->toISOString(),
            'updated_at'        => optional($this->updated_at)->toISOString(),
        ];
    }
}
