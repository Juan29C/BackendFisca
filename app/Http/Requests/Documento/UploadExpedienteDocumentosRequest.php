<?php

// app/Http/Requests/UploadExpedienteDocumentosRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadExpedienteDocumentosRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_tipo'     => ['required', 'integer', 'exists:tipos_documentos,id'],
            'codigo_doc'  => ['nullable', 'string', 'max:100'],
            'fecha_doc'   => ['nullable', 'date'],
            'descripcion' => ['nullable', 'string', 'max:65535'],
            'file'        => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20 MB
        ];
    }

    public function messages(): array
    {
        return [
            'id_tipo.required' => 'Debe seleccionar un tipo de documento.',
            'file.required'    => 'Debe adjuntar un archivo PDF.',
            'file.mimes'       => 'El archivo debe ser un PDF.',
            'file.max'         => 'El archivo no debe superar los 20 MB.',
        ];
    }
}

