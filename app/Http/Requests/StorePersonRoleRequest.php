<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'role' => [
                'required',
                Rule::in([
                    'admin_tma',
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
            'abilities' => ['nullable', 'array', 'max:20'],
            'abilities.*' => ['string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'organization_id.required' => 'Selecione a organização do papel.',
            'organization_id.exists' => 'A organização selecionada não existe.',
            'role.required' => 'Selecione o papel institucional.',
            'role.in' => 'O papel selecionado é inválido.',
            'abilities.max' => 'Selecione no máximo 20 habilidades específicas.',
        ];
    }
}
