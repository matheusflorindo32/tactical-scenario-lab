<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'kind' => [
                'required',
                Rule::in([
                    'tma',
                    'corporation',
                    'military',
                    'school',
                    'university',
                    'prefecture',
                    'hospital',
                    'clinic',
                    'company',
                    'partner',
                    'client',
                    'other',
                ]),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da organização.',
            'name.max' => 'O nome da organização deve ter no máximo 160 caracteres.',
            'kind.required' => 'Selecione o tipo da organização.',
            'kind.in' => 'O tipo de organização selecionado é inválido.',
            'status.required' => 'Selecione o status da organização.',
            'status.in' => 'O status selecionado é inválido.',
        ];
    }
}
