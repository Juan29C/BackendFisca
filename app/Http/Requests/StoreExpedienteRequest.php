<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExpedienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta si usas policies
    }

    public function rules(): array
    {
        return [
            // Identificación (uno de los dos)
            'dni'            => ['nullable', 'string', 'max:15'],
            'ruc'            => ['nullable', 'string', 'max:15'],

            // Datos persona natural
            'nombres'        => ['nullable', 'string', 'max:150'],
            'apellidos'      => ['nullable', 'string', 'max:150'],

            // Datos persona jurídica
            'razon_social'   => ['nullable', 'string', 'max:255'],

            // Comunes
            'domicilio'      => ['nullable', 'string', 'max:65535'],
            'vinculo'        => ['nullable', 'string', 'max:100'],

            // Número usado para formar el código de expediente
            'numero_expediente' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $dni = trim((string) $this->input('dni', ''));
            $ruc = trim((string) $this->input('ruc', ''));

            if ($dni === '' && $ruc === '') {
                $v->errors()->add('dni', 'Debes enviar DNI o RUC.');
                $v->errors()->add('ruc', 'Debes enviar DNI o RUC.');
            }

            if ($dni !== '' && strlen($dni) !== 8) {
                $v->errors()->add('dni', 'El DNI debe tener 8 dígitos.');
            }

            if ($ruc !== '' && strlen($ruc) !== 11) {
                $v->errors()->add('ruc', 'El RUC debe tener 11 dígitos.');
            }
        });
    }
}
