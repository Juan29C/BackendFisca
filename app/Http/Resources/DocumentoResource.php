<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'id_expediente'=> $this->id_expediente,
            'id_tipo'      => $this->id_tipo,
            'codigo_doc'   => $this->codigo_doc,
            'fecha_doc'    => optional($this->fecha_doc)->toDateString(),
            'descripcion'  => $this->descripcion,

            // Relaciones (IDs o mínimo necesario)
            'expediente'   => $this->whenLoaded('expediente', fn () => [
                'id' => $this->expediente->id,
            ]),
            'tipo_documento'=> $this->whenLoaded('tipoDocumento', fn () => [
                'id'          => $this->tipoDocumento->id,
                'descripcion' => $this->tipoDocumento->descripcion,
            ]),
            'resoluciones' => $this->whenLoaded('resoluciones', fn () =>
                $this->resoluciones->pluck('id')
            ),

            'created_at'   => optional($this->created_at)->toISOString(),
            'updated_at'   => optional($this->updated_at)->toISOString(),
        ];
    }
}
