<?php

namespace App\Http\Requests\Coactivo;

use Illuminate\Foundation\Http\FormRequest;

class GenerarOrdenPagoTotalManualRequest extends FormRequest
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
            'monto_final' => 'required|numeric|min:0',
            
            // Datos de la orden de pago
            'fecha_orden_pago' => 'required|date',
            'porcentaje_amnistia' => 'nullable|numeric|min:0|max:100',
            'monto_descuento_amnistia' => 'nullable|numeric|min:0',
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
            'monto_final.required' => 'El monto final es obligatorio',
            'monto_final.numeric' => 'El monto final debe ser numérico',
            'fecha_orden_pago.required' => 'La fecha de orden de pago es obligatoria',
            'fecha_orden_pago.date' => 'La fecha de orden de pago debe ser una fecha válida',
            'porcentaje_amnistia.numeric' => 'El porcentaje de amnistía debe ser numérico',
            'porcentaje_amnistia.max' => 'El porcentaje de amnistía no puede ser mayor a 100',
        ];
    }
}
