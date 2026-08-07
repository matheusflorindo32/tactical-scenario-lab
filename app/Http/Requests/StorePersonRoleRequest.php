<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonRoleRequest extends FormRequest
{
    public const ROLE_OPTIONS = [
        'admin_tma' => 'Administrador TMA',
        'manager_org' => 'Gestor institucional',
        'coordinator' => 'Coordenador',
        'instructor' => 'Instrutor',
        'evaluator' => 'Avaliador',
        'student' => 'Aluno',
        'support' => 'Apoio',
        'auditor' => 'Auditor',
        'viewer' => 'Visualizador',
    ];

    public const ABILITY_OPTIONS = [
        'people.view' => 'Visualizar pessoas',
        'people.manage' => 'Gerenciar pessoas',
        'scenarios.view' => 'Visualizar cenários',
        'scenarios.manage' => 'Gerenciar cenários',
        'evaluations.manage' => 'Gerenciar avaliações',
        'reports.view' => 'Visualizar relatórios',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $abilities = $this->input('abilities');

        if (is_array($abilities)) {
            $this->merge([
                'abilities' => array_values(array_unique(array_filter(
                    $abilities,
                    static fn ($ability): bool => is_string($ability) && $ability !== '',
                ))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'role' => ['required', 'string', Rule::in(array_keys(self::ROLE_OPTIONS))],
            'abilities' => ['nullable', 'array', 'max:12'],
            'abilities.*' => ['string', Rule::in(array_keys(self::ABILITY_OPTIONS))],
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
            'abilities.array' => 'As habilidades precisam ser enviadas em uma lista válida.',
            'abilities.max' => 'Selecione no máximo 12 habilidades específicas.',
            'abilities.*.in' => 'Uma das habilidades selecionadas não pertence ao catálogo permitido.',
        ];
    }
}
