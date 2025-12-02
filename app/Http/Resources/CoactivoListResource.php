<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CoactivoListResource extends JsonResource
{
    public function toArray($request): array
    {
        // Obtener el expediente relacionado
        $expediente = $this->whenLoaded('expediente');
        $adm = $expediente?->administrado ?? null;

        // Nombre del ciudadano
        $ciudadano = null;
        if ($adm) {
            $ciudadano = (($adm->tipo ?? null) === 'juridica')
                ? ($adm->razon_social ?? '')
                : trim(($adm->nombres ?? '') . ' ' . ($adm->apellidos ?? ''));
        }

        // Documento y tipo
        $documento = null;
        $tipoDocumento = null;
        if ($adm) {
            if (($adm->tipo ?? null) === 'juridica') {
                $documento = $adm->ruc ?? null;
                $tipoDocumento = 'RUC';
            } else {
                $documento = $adm->dni ?? null;
                $tipoDocumento = 'DNI';
            }
        }

        // Calcular total de deuda original
        $totalDeuda = ($this->monto_deuda ?? 0) 
                    + ($this->monto_costas ?? 0) 
                    + ($this->monto_gastos_admin ?? 0);
        
        // Calcular saldo pendiente (deuda que queda por pagar)
        $saldoPendiente = $totalDeuda - ($this->monto_pagado ?? 0);

        return [
            'id_coactivo' => $this->id_coactivo,
            'codigo_expediente_coactivo' => $this->codigo_expediente_coactivo,
            'ciudadano' => $ciudadano,
            'documento' => $documento,
            'tipo_documento' => $tipoDocumento,
            'domicilio' => $adm->domicilio ?? null,
            'total_multa' => number_format($saldoPendiente, 2, '.', ''),
            'estado' => $this->estado,
        ];
    }
}
