<?php

namespace App\Models;

use App\Support\Normalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PersonIdentifier — documento/código operacional da pessoa.
 * A normalização de `value` é feita no boot() para garantir consistência
 * independentemente da origem (formulário, seeder, import CSV, API).
 */
class PersonIdentifier extends Model
{
    protected $fillable = [
        'person_id',
        'organization_id',
        'type',
        'value',
        'value_normalized',
        'issuer',
        'country',
        'state',
        'is_primary',
        'verified_at',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary'  => 'boolean',
            'verified_at' => 'datetime',
            'expires_at'  => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PersonIdentifier $model) {
            // Se veio value mas não veio value_normalized, calcula.
            // Se veio value_normalized fixado (import/legacy), respeita.
            $model->value_normalized = Normalizer::identifier(
                $model->type,
                $model->value,
            );
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Valor mascarado para exibição em listagens. Reveal completo só via
     * ability `pii_reveal`.
     */
    public function masked(): string
    {
        return match ($this->type) {
            'cpf'   => Normalizer::maskCpf($this->value),
            'phone' => Normalizer::maskPhone($this->value),
            default => $this->value ? substr($this->value, 0, 2) . str_repeat('*', max(0, strlen($this->value) - 2)) : '***',
        };
    }

    public function isExpiringSoon(int $daysAhead = 30): bool
    {
        return $this->expires_at
            && $this->expires_at->isFuture()
            && $this->expires_at->diffInDays(now()) <= $daysAhead;
    }
}
