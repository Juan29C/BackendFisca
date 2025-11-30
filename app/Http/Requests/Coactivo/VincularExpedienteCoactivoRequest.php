<?php

namespace App\Http\Requests\Coactivo;

use Illuminate\Foundation\Http\FormRequest;

class VincularExpedienteCoactivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_expediente' => 'required|integer|exists:expediente,id',
            'correlativo' => 'required|integer|min:1',
            'ejecutor_coactivo' => 'required|string|max:200',
            'auxiliar_coactivo' => 'nullable|string|max:200',
            'observaciones' => 'nullable|string',
            
            // Documentos
            'res_sancion_codigo' => 'nullable|string|max:50',
            'res_sancion_fecha' => 'nullable|date',
            'res_consentida_codigo' => 'nullable|string|max:50',
            'res_consentida_fecha' => 'nullable|date',
            'papeleta_codigo' => 'nullable|string|max:50',
            'papeleta_fecha' => 'nullable|date',
            'codigo_infraccion' => 'nullable|string|max:50',
            'descripcion_infraccion' => 'nullable|string',
            
            // Montos
            'monto_deuda' => 'required|numeric|min:0',
            'monto_costas' => 'nullable|numeric|min:0',
            'monto_gastos_admin' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'id_expediente.required' => 'El expediente es obligatorio',
            'id_expediente.exists' => 'El expediente no existe',
            'correlativo.required' => 'El correlativo es obligatorio',
            'correlativo.min' => 'El correlativo debe ser mayor a 0',
            'ejecutor_coactivo.required' => 'El ejecutor coactivo es obligatorio',
            'monto_deuda.required' => 'El monto de deuda es obligatorio',
            'monto_deuda.min' => 'El monto de deuda debe ser mayor o igual a 0',
        ];
    }
}
