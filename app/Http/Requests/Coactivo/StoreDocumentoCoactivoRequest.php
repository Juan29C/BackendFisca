<?php

namespace App\Http\Requests\Coactivo;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentoCoactivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_tipo_doc_coactivo' => 'required|integer|exists:tipos_documentos_coactivo,id_tipo_doc_coactivo',
            'codigo_doc' => 'nullable|string|max:50',
            'fecha_doc' => 'nullable|date',
            'file' => 'required|file|max:10240', // 10MB max
            'monto_pagado' => 'nullable|numeric|min:0|max:9999999.99',
        ];
    }

    public function messages(): array
    {
        return [
            'id_tipo_doc_coactivo.required' => 'El tipo de documento es obligatorio',
            'id_tipo_doc_coactivo.exists' => 'El tipo de documento no existe',
            'file.required' => 'El archivo es obligatorio',
            'file.file' => 'Debe proporcionar un archivo válido',
            'file.max' => 'El archivo no puede superar los 10MB',
            'monto_pagado.numeric' => 'El monto pagado debe ser un número válido',
            'monto_pagado.min' => 'El monto pagado no puede ser negativo',
            'monto_pagado.max' => 'El monto pagado excede el límite permitido',
        ];
    }
}
