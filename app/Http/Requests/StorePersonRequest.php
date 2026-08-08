<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonRequest extends FormRequest
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
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'role' => [
                'required',
                Rule::in([
                    'manager_org',
                    'coordinator',
                    'instructor',
                    'evaluator',
                    'student',
                    'support',
                    'auditor',
                    'viewer',
                ]),
            ],
            'position' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Informe o nome ou a identificação operacional.',
            'display_name.max' => 'O nome deve ter no máximo 120 caracteres.',
            'birth_date.before_or_equal' => 'A data de nascimento não pode estar no futuro.',
            'organization_id.required' => 'Selecione a organização do vínculo inicial.',
            'organization_id.exists' => 'A organização selecionada não existe.',
            'unit_id.exists' => 'A unidade selecionada não existe.',
            'role.required' => 'Selecione o papel inicial.',
            'role.in' => 'O papel selecionado é inválido.',
        ];
    }
}
