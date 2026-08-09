<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioTemplate extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'organization_id',
        'source_scenario_version_id',
        'name',
        'description',
        'status',
        'created_by_user_id',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sourceVersion(): BelongsTo
    {
        return $this->belongsTo(ScenarioVersion::class, 'source_scenario_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}
