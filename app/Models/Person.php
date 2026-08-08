<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Person — identidade operacional. Papel é sempre contextual em
 * person_roles / organization_memberships.
 *
 * Regras invariantes:
 * - display_name é o único obrigatório mínimo;
 * - cadastro pode permanecer com status incomplete;
 * - CPF, RG, e-mail e telefone nunca são obrigatórios para salvar.
 */
class Person extends Model
{
    use HasPublicUuid;
    use SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'uuid',
        'display_name',
        'social_name',
        'birth_date',
        'photo_path',
        'status',
        'merged_into',
        'created_by_user_id',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Person $person): void {
            if (blank($person->status)) {
                $person->status = 'incomplete';
            }
        });
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(PersonIdentifier::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PersonContact::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(PersonRole::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'merged_into');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isIncomplete(): bool
    {
        return $this->status === 'incomplete';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function isMerged(): bool
    {
        return $this->status === 'merged';
    }

    /**
     * Campos opcionais ainda não preenchidos. Ausência de documento ou
     * contato não invalida o cadastro mínimo.
     *
     * @return array<int, string>
     */
    public function pendingFields(): array
    {
        $pending = [];

        if (blank($this->birth_date)) {
            $pending[] = 'birth_date';
        }

        if (blank($this->photo_path)) {
            $pending[] = 'photo_path';
        }

        if (! $this->relationLoaded('identifiers') && ! $this->identifiers()->exists()) {
            $pending[] = 'documents';
        } elseif ($this->relationLoaded('identifiers') && $this->identifiers->isEmpty()) {
            $pending[] = 'documents';
        }

        if (! $this->relationLoaded('contacts') && ! $this->contacts()->exists()) {
            $pending[] = 'contacts';
        } elseif ($this->relationLoaded('contacts') && $this->contacts->isEmpty()) {
            $pending[] = 'contacts';
        }

        return $pending;
    }

    public function preferredName(): string
    {
        return filled($this->social_name) ? $this->social_name : $this->display_name;
    }
}
