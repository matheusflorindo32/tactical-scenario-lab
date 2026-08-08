<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class AssessmentEvidence extends Model
{
    use HasPublicUuid;

    protected $table = 'assessment_evidence';

    protected $fillable = [
        'uuid',
        'assessment_criterion_id',
        'execution_event_id',
        'statement',
        'observed_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'observed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AssessmentEvidence $evidence): void {
            $criterion = AssessmentCriterion::query()
                ->with('assessment.execution')
                ->findOrFail($evidence->assessment_criterion_id);
            $execution = $criterion->assessment->execution;

            if (trim((string) $evidence->statement) === '') {
                throw new InvalidArgumentException('Assessment evidence statement is required.');
            }

            $observedAt = $evidence->observed_at;

            if ($execution->started_at && $observedAt->lt($execution->started_at)) {
                throw new InvalidArgumentException('Evidence cannot precede execution start.');
            }

            if ($execution->completed_at && $observedAt->gt($execution->completed_at)) {
                throw new InvalidArgumentException('Evidence cannot exceed execution completion.');
            }

            if ($evidence->execution_event_id) {
                $event = ExecutionEvent::query()->findOrFail($evidence->execution_event_id);

                if ($event->scenario_execution_id !== $execution->id) {
                    throw new InvalidArgumentException('Evidence event must belong to the same execution.');
                }
            }
        });
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(AssessmentCriterion::class, 'assessment_criterion_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ExecutionEvent::class, 'execution_event_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
