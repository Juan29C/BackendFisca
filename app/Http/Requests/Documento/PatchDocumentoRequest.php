<?php

namespace App\Http\Requests\Documento;

use Illuminate\Foundation\Http\FormRequest;

class PatchDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta si aplicas políticas
    }

    public function rules(): array
    {
        return [
            'id_tipo'     => ['sometimes', 'integer', 'exists:tipos_documentos,id'],
            'codigo_doc'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'fecha_doc'   => ['sometimes', 'nullable', 'date'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'file'        => ['sometimes', 'file', 'mimes:pdf', 'max:20480'], // 20MB
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'El archivo debe ser un PDF.',
            'file.max'   => 'El archivo no debe superar los 20MB.',
        ];
    }
}
