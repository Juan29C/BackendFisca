<?php

namespace App\Http\Requests\EntidadBancaria;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntidadBancariaRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'ruc' => ['sometimes', 'string', 'max:15', 'unique:entidades_bancarias,ruc,' . $id . ',id_entidad_bancaria'],
            'direccion' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.string' => 'El nombre debe ser texto',
            'ruc.unique' => 'El RUC ya está registrado',
            'direccion.string' => 'La dirección debe ser texto',
        ];
    }
}
