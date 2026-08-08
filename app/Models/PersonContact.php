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
 * Canal de contato protegido.
 *
 * O valor integral é criptografado e não aparece na serialização padrão.
 */
class PersonContact extends Model
{
    use HasPublicUuid;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'person_id',
        'organization_id',
        'type',
        'value',
        'label',
        'is_primary',
        'verified_at',
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
        ];
    }

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => filled($this->value_encrypted)
                ? Crypt::decryptString($this->value_encrypted)
                : null,
            set: function (?string $value): array {
                $type = (string) $this->type;
                $normalized = Normalizer::contact($type, $value);

                return [
                    'value_encrypted' => Crypt::encryptString((string) $value),
                    'value_fingerprint' => Normalizer::fingerprint('contact', $type, $value),
                    'masked_value' => $this->maskValue($type, $normalized),
                ];
            },
        );
    }

    public static function fingerprintFor(string $type, ?string $value): string
    {
        return Normalizer::fingerprint('contact', $type, $value);
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

    private function maskValue(string $type, ?string $value): string
    {
        return match ($type) {
            'phone', 'whatsapp', 'emergency' => Normalizer::maskPhone($value),
            'email' => Normalizer::maskEmail($value),
            default => Normalizer::maskGeneric($value),
        };
    }
}
