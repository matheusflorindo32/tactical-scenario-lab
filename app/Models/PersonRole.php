<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonRole extends Model
{
    protected $fillable = [
        'person_id',
        'organization_id',
        'role',
        'abilities',
        'granted_at',
        'granted_by_user_id',
        'revoked_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'abilities'  => 'array',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isActive(): bool
    {
        return blank($this->revoked_at);
    }

    /** Confere se esta role concede uma ability específica. */
    public function hasAbility(string $ability): bool
    {
        return is_array($this->abilities) && in_array($ability, $this->abilities, true);
    }
}
