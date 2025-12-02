<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TipoDocumentoCoactivoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id_tipo_doc_coactivo'          => $this->id_tipo_doc_coactivo,
            'descripcion'                   => $this->descripcion,

            'documentos'  => $this->whenLoaded('documentos', fn () =>
                $this->documentos->pluck('id_doc_coactivo')
            ),
        ];
    }
}
