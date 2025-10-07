<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResolucionDocumentoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id_resolucion' => $this->id_resolucion,
            'id_documento'  => $this->id_documento,
        ];
    }
}
