<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:120'],
            'social_name' => ['nullable', 'string', 'max:120'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['active', 'incomplete', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Informe o nome ou a identificação operacional.',
            'display_name.max' => 'O nome deve ter no máximo 120 caracteres.',
            'birth_date.before_or_equal' => 'A data de nascimento não pode estar no futuro.',
            'status.required' => 'Selecione a situação do cadastro.',
            'status.in' => 'A situação selecionada é inválida.',
        ];
    }
}
