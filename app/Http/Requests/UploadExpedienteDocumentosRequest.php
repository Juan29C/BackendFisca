<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadExpedienteDocumentosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta si usas auth/policies
    }

    public function rules(): array
    {
        return [
            // route param {id} validado en el Controller (exists)
            'files'     => ['nullable','array'],
            'files.*'   => ['file','mimes:pdf','max:20480'], // 20MB por archivo
        ];
    }

    public function messages(): array
    {
        return [
            'files.*.mimes' => 'Cada archivo debe ser un PDF.',
            'files.*.max'   => 'Cada archivo no debe superar los 20MB.',
        ];
    }
}
