<?php

namespace App\Http\Requests\Expediente;

use Illuminate\Foundation\Http\FormRequest;

class ResolverApelacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['hubo_apelacion' => ['required','boolean']];
    }
}
