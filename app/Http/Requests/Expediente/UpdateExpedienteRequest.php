<?php

namespace App\Http\Requests\Expediente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpedienteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Expediente
            'fecha_inicio'      => ['sometimes','date'],
            'id_estado'         => ['sometimes','integer','exists:estado_expediente,id'],
            'codigo_expediente' => ['sometimes','integer','min:1'], // si lo vas a permitir
            'asunto'            => ['sometimes','nullable','string'],

            // Administrado (parcial)
            'administrado' => ['sometimes','array'],
            'administrado.tipo'         => ['sometimes','in:natural,juridica'],
            'administrado.dni'          => ['sometimes','nullable','string','max:15'],
            'administrado.ruc'          => ['sometimes','nullable','string','max:15'],
            'administrado.nombres'      => ['sometimes','nullable','string','max:150'],
            'administrado.apellidos'    => ['sometimes','nullable','string','max:150'],
            'administrado.razon_social' => ['sometimes','nullable','string','max:255'],
            'administrado.domicilio'    => ['sometimes','nullable','string'],
            'administrado.vinculo'      => ['sometimes','nullable','string','max:100'],
        ];
    }
}
