<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\RoleEnum;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => ['required','string','max:255'],
            'last_name'  => ['required','string','max:255'],
            'email'      => ['required','email','max:255','unique:users,email'],
            'password'   => ['required','string','min:8'],
            'role'       => ['required', Rule::in([
                RoleEnum::FISCALIZACION->value,
                RoleEnum::COACTIVO->value,
                RoleEnum::USUARIO->value,
            ])],
        ];
    }
}
