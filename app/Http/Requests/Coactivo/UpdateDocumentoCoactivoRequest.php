<?php

namespace App\Http\Requests\Coactivo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentoCoactivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_tipo_doc_coactivo' => 'sometimes|integer|exists:tipos_documentos_coactivo,id_tipo_doc_coactivo',
            'codigo_doc' => 'nullable|string|max:50',
            'fecha_doc' => 'nullable|date',
            'file' => 'sometimes|file|max:10240', // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'id_tipo_doc_coactivo.exists' => 'El tipo de documento no existe',
            'file.file' => 'Debe proporcionar un archivo válido',
            'file.max' => 'El archivo no puede superar los 10MB',
        ];
    }
}
