<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpedienteListResource extends JsonResource
{
    public function toArray($request): array
    {
        $adm    = $this->whenLoaded('administrado');
        $estado = $this->whenLoaded('estado');

        // Nombre mostrado
        $ciudadano = null;
        if ($adm) {
            $ciudadano = (($adm->tipo ?? null) === 'juridica')
                ? ($adm->razon_social ?? '')
                : trim(($adm->nombres ?? '') . ' ' . ($adm->apellidos ?? ''));
        }

        // Documento mostrado (RUC si jurídica, DNI si natural)
        $documento = null;
        $tipoDocumento = null;
        if ($adm) {
            if (($adm->tipo ?? null) === 'juridica') {
                $documento     = $adm->ruc ?? null;
                $tipoDocumento = 'RUC';
            } else {
                $documento     = $adm->dni ?? null;
                $tipoDocumento = 'DNI';
            }
        }

        return [
            'id'                => $this->id,
            'codigo_expediente' => $this->codigo_expediente,
            'ciudadano'         => $ciudadano,
            'documento'         => $documento,        // <- antes 'dni'
            'tipo_documento'    => $tipoDocumento,    // <- opcional, útil para el front
            'domicilio'         => $adm->domicilio ?? null,
            'estado'            => $estado->nombre ?? null,
        ];
    }
}
