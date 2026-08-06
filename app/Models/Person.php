<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Person — identidade operacional. Papel é sempre contextual em
 * person_roles / organization_memberships.
 *
 * Regras invariantes:
 * - display_name é o único obrigatório mínimo (ver §5.1 do plano).
 * - Cadastro salva com status 'incomplete'; só vira 'active' quando
 *   passa nos critérios definidos pela organização.
 * - CPF/RG/e-mail/telefone NUNCA são obrigatórios para salvar.
 */
class Person extends Model
{
    use HasUuids;
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

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // --------------------------------------------------------------
    // Relacionamentos
    // --------------------------------------------------------------

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

    // --------------------------------------------------------------
    // Guards de status
    // --------------------------------------------------------------

    public function isActive(): bool     { return $this->status === 'active'; }
    public function isIncomplete(): bool { return $this->status === 'incomplete'; }
    public function isInactive(): bool   { return $this->status === 'inactive'; }
    public function isMerged(): bool     { return $this->status === 'merged'; }

    /**
     * Retorna a lista de campos "opcionais mas ausentes" — usado pela
     * UI para desenhar o `x-completeness-bar` e badges de pendência.
     */
    public function pendingFields(): array
    {
        $pending = [];

        if (blank($this->birth_date))         $pending[] = 'birth_date';
        if (blank($this->photo_path))         $pending[] = 'photo_path';
        if ($this->identifiers()->count() === 0) $pending[] = 'documents';
        if ($this->contacts()->count() === 0)    $pending[] = 'contacts';

        return $pending;
    }

    /**
     * Rótulo de exibição preferido — social_name quando presente,
     * senão display_name (nunca mostra CPF em listagens).
     */
    public function preferredName(): string
    {
        return filled($this->social_name) ? $this->social_name : $this->display_name;
    }
}
