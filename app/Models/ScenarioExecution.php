<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScenarioExecution extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'organization_id',
        'scenario_version_id',
        'sequence_number',
        'status',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scenarioVersion(): BelongsTo
    {
        return $this->belongsTo(ScenarioVersion::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(ExecutionTeam::class)->orderBy('label');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExecutionParticipant::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canStart(): bool
    {
        return $this->isDraft();
    }

    public function canComplete(): bool
    {
        return $this->isRunning();
    }

    public function canCancel(): bool
    {
        return $this->isDraft() || $this->isRunning();
    }

    public function canConfigure(): bool
    {
        return $this->isDraft() || $this->isRunning();
    }
}
