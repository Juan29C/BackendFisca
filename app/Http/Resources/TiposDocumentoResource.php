<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TiposDocumentoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'descripcion' => $this->descripcion,

            'documentos'  => $this->whenLoaded('documentos', fn () =>
                $this->documentos->pluck('id')
            ),
        ];
    }
}
