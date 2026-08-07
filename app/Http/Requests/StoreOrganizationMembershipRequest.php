<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'position' => ['nullable', 'string', 'max:120'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'organization_id.required' => 'Selecione a organização do vínculo.',
            'organization_id.exists' => 'A organização selecionada não existe.',
            'unit_id.exists' => 'A unidade selecionada não existe.',
            'ended_at.after_or_equal' => 'A data de término não pode ser anterior à data de início.',
            'status.in' => 'O status informado é inválido.',
        ];
    }
}
