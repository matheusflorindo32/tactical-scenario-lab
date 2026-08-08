<?php

namespace App\Services\People;

use App\Models\PersonContact;
use App\Models\PersonIdentifier;
use Illuminate\Database\Eloquent\Builder;

/**
 * Busca universal segura de pessoas.
 *
 * Nomes aceitam busca parcial. Documentos e contatos são consultados somente
 * por equivalência exata através de fingerprints HMAC, sem descriptografar
 * colunas e sem usar PII integral em LIKE.
 */
final class PersonSearch
{
    /** @var array<int, string> */
    private const IDENTIFIER_TYPES = [
        'cpf',
        'rg',
        'id_funcional',
        'matricula',
        'passaporte',
        'registro_profissional',
        'temp_code',
        'qr',
        'other',
    ];

    /** @var array<int, string> */
    private const CONTACT_TYPES = [
        'email',
        'phone',
        'whatsapp',
        'emergency',
        'other',
    ];

    public function apply(Builder $query, string $term, ?int $organizationId = null): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $identifierFingerprints = collect(self::IDENTIFIER_TYPES)
            ->mapWithKeys(fn (string $type): array => [
                $type => PersonIdentifier::fingerprintFor($type, $term),
            ]);

        $contactFingerprints = collect(self::CONTACT_TYPES)
            ->mapWithKeys(fn (string $type): array => [
                $type => PersonContact::fingerprintFor($type, $term),
            ]);

        return $query->where(function (Builder $people) use ($term, $organizationId, $identifierFingerprints, $contactFingerprints): void {
            $people
                ->where('display_name', 'like', '%'.$this->escapeLike($term).'%')
                ->orWhere('social_name', 'like', '%'.$this->escapeLike($term).'%')
                ->orWhereHas('identifiers', function (Builder $identifiers) use ($organizationId, $identifierFingerprints): void {
                    $identifiers
                        ->when($organizationId, fn (Builder $builder) => $builder->where('organization_id', $organizationId))
                        ->where(function (Builder $matches) use ($identifierFingerprints): void {
                            foreach ($identifierFingerprints as $type => $fingerprint) {
                                $matches->orWhere(function (Builder $candidate) use ($type, $fingerprint): void {
                                    $candidate
                                        ->where('type', $type)
                                        ->where('value_fingerprint', $fingerprint);
                                });
                            }
                        });
                })
                ->orWhereHas('contacts', function (Builder $contacts) use ($organizationId, $contactFingerprints): void {
                    $contacts
                        ->when($organizationId, fn (Builder $builder) => $builder->where('organization_id', $organizationId))
                        ->where(function (Builder $matches) use ($contactFingerprints): void {
                            foreach ($contactFingerprints as $type => $fingerprint) {
                                $matches->orWhere(function (Builder $candidate) use ($type, $fingerprint): void {
                                    $candidate
                                        ->where('type', $type)
                                        ->where('value_fingerprint', $fingerprint);
                                });
                            }
                        });
                });
        });
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
