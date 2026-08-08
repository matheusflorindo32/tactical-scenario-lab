<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;
use LogicException;

class ExecutionAssessment extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'organization_id',
        'scenario_execution_id',
        'source',
        'status',
        'pass_threshold',
        'base_score',
        'penalty_points',
        'evaluator_adjustment',
        'adjustment_justification',
        'final_score',
        'result',
        'automatic_fail',
        'finalized_at',
        'finalized_by_user_id',
        'legacy_imported_at',
    ];

    protected function casts(): array
    {
        return [
            'pass_threshold' => 'decimal:2',
            'base_score' => 'decimal:2',
            'penalty_points' => 'decimal:2',
            'evaluator_adjustment' => 'integer',
            'final_score' => 'decimal:2',
            'automatic_fail' => 'boolean',
            'finalized_at' => 'datetime',
            'legacy_imported_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ExecutionAssessment $assessment): void {
            if (! $assessment->scenario_execution_id || ! $assessment->organization_id) {
                return;
            }

            $executionOrganizationId = ScenarioExecution::query()
                ->whereKey($assessment->scenario_execution_id)
                ->value('organization_id');

            if ($executionOrganizationId === null || (int) $executionOrganizationId !== (int) $assessment->organization_id) {
                throw new InvalidArgumentException('Assessment organization must match execution organization.');
            }
        });

        static::updating(function (ExecutionAssessment $assessment): void {
            if ($assessment->getOriginal('status') === 'finalized') {
                throw new LogicException('Finalized assessment content is immutable.');
            }
        });

        static::deleting(function (ExecutionAssessment $assessment): void {
            if ($assessment->isFinalized()) {
                throw new LogicException('Finalized assessment content is immutable.');
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(ScenarioExecution::class, 'scenario_execution_id');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(AssessmentCriterion::class)->orderBy('position');
    }

    public function criticalErrorOccurrences(): HasMany
    {
        return $this->hasMany(CriticalErrorOccurrence::class)->orderBy('id');
    }

    public function keyTimes(): HasMany
    {
        return $this->hasMany(KeyTimeRecord::class)
            ->orderBy('occurred_at')
            ->orderBy('id');
    }

    public function debrief(): HasOne
    {
        return $this->hasOne(ExecutionDebrief::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }
}
