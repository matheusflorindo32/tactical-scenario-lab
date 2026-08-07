<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'type' => ['required', Rule::in(['email', 'phone', 'whatsapp', 'emergency', 'other'])],
            'value' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:60'],
            'is_primary' => ['nullable', 'boolean'],
            'confirmed_duplicate' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'organization_id.required' => 'Selecione a organização relacionada ao contato.',
            'type.required' => 'Selecione o tipo de contato.',
            'type.in' => 'O tipo de contato selecionado é inválido.',
            'value.required' => 'Informe o contato.',
            'value.max' => 'O contato deve ter no máximo 255 caracteres.',
        ];
    }
}
