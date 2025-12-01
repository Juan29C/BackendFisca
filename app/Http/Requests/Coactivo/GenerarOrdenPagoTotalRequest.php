<?php

namespace App\Http\Requests\Coactivo;

use Illuminate\Foundation\Http\FormRequest;

class GenerarOrdenPagoTotalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_final' => 'required|numeric|min:0',
            'fecha_orden_pago' => 'required|date',
            'porcentaje_amnistia' => 'nullable|numeric|min:0|max:100',
            'monto_descuento_amnistia' => 'nullable|numeric|min:0',
            'ordenanza' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'monto_final.required' => 'El monto final es obligatorio',
            'monto_final.min' => 'El monto final debe ser mayor o igual a 0',
            'fecha_orden_pago.required' => 'La fecha de orden de pago es obligatoria',
            'fecha_orden_pago.date' => 'La fecha de orden de pago debe ser una fecha válida',
            'porcentaje_amnistia.max' => 'El porcentaje de amnistía no puede ser mayor a 100',
            'monto_descuento_amnistia.min' => 'El monto de descuento debe ser mayor o igual a 0',
        ];
    }
}
