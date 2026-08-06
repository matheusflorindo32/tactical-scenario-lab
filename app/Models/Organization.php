<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'kind',
        'status',
        'notes',
    ];

    /**
     * PK continua sendo `id` BIGINT — HasUuids do Laravel substituiria a
     * PK, que não é o que queremos. Geramos UUID à parte para URLs opacas.
     */
    protected static function booted(): void
    {
        static::creating(function (Organization $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // --------------------------------------------------------------
    // Relacionamentos
    // --------------------------------------------------------------

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function personIdentifiers(): HasMany
    {
        return $this->hasMany(PersonIdentifier::class);
    }

    public function personContacts(): HasMany
    {
        return $this->hasMany(PersonContact::class);
    }

    public function personRoles(): HasMany
    {
        return $this->hasMany(PersonRole::class);
    }

    // --------------------------------------------------------------
    // Guards de status
    // --------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
