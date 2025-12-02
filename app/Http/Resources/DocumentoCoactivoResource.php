<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DocumentoCoactivoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_documento_coactivo' => $this->id_doc_coactivo,
            'id_coactivo' => $this->id_coactivo,
            'id_tipo_doc_coactivo' => $this->id_tipo_doc_coactivo,
            'nombreDocumento' => $this->tipoDocumento?->descripcion,
            'codigo_doc' => $this->codigo_doc,
            'fecha_doc' => $this->fecha_doc,
            'descripcion' => $this->descripcion,
            'ruta' => $this->ruta,
            'url' => $this->ruta ? asset('storage/' . $this->ruta) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
