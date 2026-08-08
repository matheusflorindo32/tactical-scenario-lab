<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'name',
        'kind',
        'status',
        'notes',
    ];

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

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
