<?php

namespace App\Http\Requests\EntidadBancaria;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntidadBancariaRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'ruc' => ['required', 'string', 'max:15', 'unique:entidades_bancarias,ruc'],
            'direccion' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'ruc.required' => 'El RUC es obligatorio',
            'ruc.unique' => 'El RUC ya está registrado',
            'direccion.required' => 'La dirección es obligatoria',
        ];
    }
}
