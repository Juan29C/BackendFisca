<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResolucionFromSpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta si tienes auth/policies
    }

    public function rules(): array
    {
        return [
            'codigo_resolucion'   => ['required', 'integer', 'min:1'],
            'id_tipo_resolucion'  => ['required', 'integer', 'min:1', 'exists:tipo_resolucion,id'],
            'template'            => ['required', 'string', 'in:resolucionNoHaLugar,resolucionContinuar'],
            'documentos'                  => ['nullable', 'array'],
            'documentos.*.codigo_doc'     => ['required', 'string', 'max:150'],
            'documentos.*.fecha_doc'      => ['required', 'date_format:Y-m-d'],
            'documentos.*.id_tipo'        => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'     => 'El campo :attribute es obligatorio.',
            'integer'      => 'El campo :attribute debe ser un número entero.',
            'min'          => 'El campo :attribute debe ser al menos :min.',
            'date_format'  => 'El campo :attribute debe tener el formato :format.',
            'exists'       => 'El :attribute no existe.',
            'documentos.*.codigo_doc.required' => 'Cada documento debe tener un código.',
            'documentos.*.fecha_doc.required'  => 'Cada documento debe tener una fecha.',
            'documentos.*.id_tipo.required'    => 'Cada documento debe indicar el tipo (id_tipo).',
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo_resolucion'  => 'código de resolución',
            'id_tipo_resolucion' => 'tipo de resolución',
            'template'           => 'plantilla',
            'documentos'                     => 'documentos',
            'documentos.*.codigo_doc'        => 'código del documento',
            'documentos.*.fecha_doc'         => 'fecha del documento',
            'documentos.*.id_tipo'           => 'tipo de documento',
        ];
    }
}
