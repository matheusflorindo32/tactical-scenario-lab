<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['person_id', 'name', 'email', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function organizationAccesses(): HasMany
    {
        return $this->hasMany(UserOrganizationAccess::class);
    }

    public function activeOrganizationAccesses(): HasMany
    {
        return $this->organizationAccesses()->whereNull('revoked_at');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasOrganizationAccess(int $organizationId): bool
    {
        return $this->activeOrganizationAccesses()
            ->where('organization_id', $organizationId)
            ->exists();
    }
}
