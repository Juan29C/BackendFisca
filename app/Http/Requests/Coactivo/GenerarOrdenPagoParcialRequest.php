<?php

namespace App\Http\Requests\Coactivo;

use Illuminate\Foundation\Http\FormRequest;

class GenerarOrdenPagoParcialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_pagado' => 'required|numeric|min:0',
            'fecha_pago' => 'required|date',
            'monto_saldo_pendiente' => 'required|numeric|min:0',
            'ordenanza' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'monto_pagado.required' => 'El monto pagado es obligatorio',
            'monto_pagado.min' => 'El monto pagado debe ser mayor o igual a 0',
            'fecha_pago.required' => 'La fecha de pago es obligatoria',
            'fecha_pago.date' => 'La fecha de pago debe ser una fecha válida',
            'monto_saldo_pendiente.required' => 'El saldo pendiente es obligatorio',
            'monto_saldo_pendiente.min' => 'El saldo pendiente debe ser mayor o igual a 0',
        ];
    }
}
