<?php

namespace App\Http\Requests\Coactivo;

use Illuminate\Foundation\Http\FormRequest;

class GenerarOrdenPagoParcialManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Datos del expediente coactivo
            'cod_expediente_coactivo' => 'required|string|max:100',
            
            // Datos del administrado
            'nombre_completo' => 'required|string|max:255',
            'documento' => 'required|string|max:20',
            'direccion' => 'required|string|max:255',
            
            // Datos de resolución/sanción
            'cod_sancion' => 'required|string|max:50',
            
            // Montos
            'monto_total' => 'required|numeric|min:0',
            'monto_pagado' => 'required|numeric|min:0',
            'monto_saldo_pendiente' => 'required|numeric|min:0',
            
            // Datos de la orden de pago
            'fecha_pago' => 'required|date',
            'ordenanza' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'cod_expediente_coactivo.required' => 'El código de expediente coactivo es obligatorio',
            'nombre_completo.required' => 'El nombre completo es obligatorio',
            'documento.required' => 'El documento (DNI/RUC) es obligatorio',
            'direccion.required' => 'La dirección es obligatoria',
            'cod_sancion.required' => 'El código de sanción es obligatorio',
            'monto_total.required' => 'El monto total es obligatorio',
            'monto_total.numeric' => 'El monto total debe ser numérico',
            'monto_pagado.required' => 'El monto pagado es obligatorio',
            'monto_pagado.numeric' => 'El monto pagado debe ser numérico',
            'monto_saldo_pendiente.required' => 'El monto saldo pendiente es obligatorio',
            'monto_saldo_pendiente.numeric' => 'El monto saldo pendiente debe ser numérico',
            'fecha_pago.required' => 'La fecha de pago es obligatoria',
            'fecha_pago.date' => 'La fecha de pago debe ser una fecha válida',
        ];
    }
}
