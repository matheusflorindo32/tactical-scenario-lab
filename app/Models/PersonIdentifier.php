<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Support\Normalizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * Documento ou código operacional protegido.
 *
 * O valor integral é criptografado, nunca serializado por padrão. Pesquisa
 * exata e duplicidade usam uma impressão digital HMAC determinística.
 */
class PersonIdentifier extends Model
{
    use HasPublicUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'person_id',
        'organization_id',
        'type',
        'value',
        'issuer',
        'country',
        'state',
        'is_primary',
        'verified_at',
        'expires_at',
        'notes',
    ];

    protected $hidden = [
        'value_encrypted',
        'value_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
            'expires_at' => 'date',
        ];
    }

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => filled($this->value_encrypted)
                ? Crypt::decryptString($this->value_encrypted)
                : null,
            set: function (?string $value): array {
                $normalized = Normalizer::identifier((string) $this->type, $value);

                return [
                    'value_encrypted' => Crypt::encryptString((string) $value),
                    'value_fingerprint' => Normalizer::fingerprint('identifier', (string) $this->type, $value),
                    'masked_value' => $this->maskValue((string) $this->type, $normalized),
                ];
            },
        );
    }

    public static function fingerprintFor(string $type, ?string $value): string
    {
        return Normalizer::fingerprint('identifier', $type, $value);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function masked(): string
    {
        return $this->masked_value;
    }

    public function isExpiringSoon(int $daysAhead = 30): bool
    {
        return $this->expires_at
            && $this->expires_at->isFuture()
            && $this->expires_at->diffInDays(now()) <= $daysAhead;
    }

    private function maskValue(string $type, ?string $value): string
    {
        return match ($type) {
            'cpf' => Normalizer::maskCpf($value),
            default => Normalizer::maskGeneric($value),
        };
    }
}
