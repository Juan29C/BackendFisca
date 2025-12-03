<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DocumentosExpedienteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'id_tipo'       => $this->id_tipo,
            'codigo_doc'    => $this->codigo_doc,
            'fecha_doc'     => $this->fecha_doc,
            'url'           => $this->ruta ? asset('uploads/' . $this->ruta) : null,
            'tipo'          => $this->whenLoaded('tipoDocumento', fn () => $this->tipoDocumento->descripcion),
        ];
    }
}

