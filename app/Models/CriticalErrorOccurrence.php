<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class CriticalErrorOccurrence extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'execution_assessment_id',
        'catalog_label_snapshot',
        'rule',
        'penalty_points',
        'execution_event_id',
        'observed_at',
        'notes',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'penalty_points' => 'decimal:2',
            'observed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CriticalErrorOccurrence $occurrence): void {
            $assessment = ExecutionAssessment::query()
                ->with('execution.scenarioVersion')
                ->findOrFail($occurrence->execution_assessment_id);
            $execution = $assessment->execution;
            $source = (string) ($occurrence->source ?: 'm4');
            $rule = (string) ($occurrence->rule ?: 'record');
            $penalty = (float) ($occurrence->penalty_points ?? 0);

            if (! in_array($source, ['m4', 'legacy'], true)) {
                throw new InvalidArgumentException('Unknown critical error occurrence source.');
            }

            if (! in_array($rule, ['record', 'penalty', 'automatic_fail'], true)) {
                throw new InvalidArgumentException('Unknown critical error occurrence rule.');
            }

            if (trim((string) $occurrence->catalog_label_snapshot) === '') {
                throw new InvalidArgumentException('Critical error label is required.');
            }

            if ($source === 'm4') {
                $catalog = $execution->scenarioVersion->critical_errors ?? [];

                if (! is_array($catalog) || ! in_array($occurrence->catalog_label_snapshot, $catalog, true)) {
                    throw new InvalidArgumentException('Critical error must exist in the scenario version catalog.');
                }
            }

            if ($rule === 'penalty') {
                if ($penalty <= 0 || $penalty > 100) {
                    throw new InvalidArgumentException('Penalty points must be greater than zero and at most 100.');
                }
            } elseif ($penalty !== 0.0) {
                throw new InvalidArgumentException('Record and automatic-fail rules cannot carry numerical penalty points.');
            }

            if ($source === 'legacy' && $rule !== 'record') {
                throw new InvalidArgumentException('Legacy critical errors cannot infer penalty or automatic-fail semantics.');
            }

            $observedAt = $occurrence->observed_at;

            if ($execution->started_at && $observedAt->lt($execution->started_at)) {
                throw new InvalidArgumentException('Critical error observation cannot precede execution start.');
            }

            if ($execution->completed_at && $observedAt->gt($execution->completed_at)) {
                throw new InvalidArgumentException('Critical error observation cannot exceed execution completion.');
            }

            if ($occurrence->execution_event_id) {
                $event = ExecutionEvent::query()->findOrFail($occurrence->execution_event_id);

                if ($event->scenario_execution_id !== $execution->id) {
                    throw new InvalidArgumentException('Critical error event must belong to the same execution.');
                }
            }
        });
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ExecutionAssessment::class, 'execution_assessment_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ExecutionEvent::class, 'execution_event_id');
    }
}
