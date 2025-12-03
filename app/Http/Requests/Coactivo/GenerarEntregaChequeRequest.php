<?php

namespace App\Http\Requests\Coactivo;

use Illuminate\Foundation\Http\FormRequest;

class GenerarEntregaChequeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_entidad_bancaria' => 'required|integer|exists:entidades_bancarias,id_entidad_bancaria',
            'fecha_recepcion_bancaria' => 'required|date',
            'monto_retencion' => 'required|numeric|min:0',
            'cod_orden_bancario' => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'id_entidad_bancaria.required' => 'La entidad bancaria es obligatoria',
            'id_entidad_bancaria.exists' => 'La entidad bancaria seleccionada no existe',
            'fecha_recepcion_bancaria.required' => 'La fecha de recepción bancaria es obligatoria',
            'fecha_recepcion_bancaria.date' => 'La fecha de recepción bancaria debe ser una fecha válida',
            'monto_retencion.required' => 'El monto de retención es obligatorio',
            'monto_retencion.numeric' => 'El monto de retención debe ser numérico',
            'monto_retencion.min' => 'El monto de retención debe ser mayor a 0',
            'cod_orden_bancario.required' => 'El código de orden bancario es obligatorio',
            'cod_orden_bancario.max' => 'El código de orden bancario no debe exceder 100 caracteres',
        ];
    }
}
