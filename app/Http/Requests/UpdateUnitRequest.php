<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'name' => ['required', 'string', 'max:160'],
            'kind' => ['required', Rule::in(['headquarters', 'regional', 'department', 'division', 'battalion', 'company', 'platoon', 'station', 'school', 'clinic', 'other'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da unidade.',
            'name.max' => 'O nome da unidade deve ter no máximo 160 caracteres.',
            'parent_unit_id.exists' => 'A unidade superior selecionada não existe.',
            'kind.in' => 'O tipo de unidade selecionado é inválido.',
            'status.in' => 'O status selecionado é inválido.',
        ];
    }
}
