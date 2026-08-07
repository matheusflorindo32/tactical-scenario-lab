<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonIdentifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'type' => ['required', Rule::in([
                'cpf',
                'rg',
                'id_funcional',
                'matricula',
                'passaporte',
                'registro_profissional',
                'temp_code',
                'qr',
                'other',
            ])],
            'value' => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:60'],
            'country' => ['nullable', 'string', 'size:2'],
            'state' => ['nullable', 'string', 'size:2'],
            'expires_at' => ['nullable', 'date'],
            'is_primary' => ['nullable', 'boolean'],
            'confirm_duplicate' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'organization_id.required' => 'Selecione a organização responsável pelo identificador.',
            'type.required' => 'Selecione o tipo do identificador.',
            'value.required' => 'Informe o valor do identificador.',
            'country.size' => 'Use a sigla do país com dois caracteres.',
            'state.size' => 'Use a sigla do estado com dois caracteres.',
        ];
    }
}
