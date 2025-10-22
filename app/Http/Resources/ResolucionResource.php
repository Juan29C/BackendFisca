<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResolucionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'id_expediente'      => $this->id_expediente,
            'numero'             => $this->numero,
            'fecha'              => optional($this->fecha)->format('Y-m-d'),
            'lugar_emision'      => $this->lugar_emision,
            'texto'              => $this->texto,
            'tipo_resolucion'    => $this->whenLoaded('tipoResolucion', fn() => [
                'id'          => $this->tipoResolucion->id,
                'descripcion' => $this->tipoResolucion->descripcion ?? null,
            ]),
            'documentos'         => $this->whenLoaded('documentos', fn() =>
                $this->documentos->map(fn($d) => [
                    'id'          => $d->id,
                    'id_tipo'     => $d->id_tipo,
                    'codigo_doc'  => $d->codigo_doc,
                    'fecha_doc'   => optional($d->fecha_doc)->format('Y-m-d'),
                    'descripcion' => $d->descripcion,
                    'ruta'        => $d->ruta,
                    'tipo'        => [
                        'id'          => optional($d->tipoDocumento)->id,
                        'descripcion' => optional($d->tipoDocumento)->descripcion,
                    ],
                ])
            ),
            'file_url' => $this->getAttribute('file_url'),
        ];
    }
}
