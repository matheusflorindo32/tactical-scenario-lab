<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class KeyTimeRecord extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'execution_assessment_id',
        'label',
        'occurred_at',
        'reference_seconds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'elapsed_seconds' => 'integer',
            'reference_seconds' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (KeyTimeRecord $record): void {
            $assessment = ExecutionAssessment::query()
                ->with('execution')
                ->findOrFail($record->execution_assessment_id);
            $execution = $assessment->execution;

            if ($assessment->isFinalized()) {
                throw new LogicException('Finalized key time records are immutable.');
            }

            if (trim((string) $record->label) === '') {
                throw new InvalidArgumentException('Key time label is required.');
            }

            if (! $execution->started_at) {
                throw new InvalidArgumentException('Execution must be started before recording a key time.');
            }

            $occurredAt = $record->occurred_at;

            if ($occurredAt->lt($execution->started_at)) {
                throw new InvalidArgumentException('Key time cannot precede execution start.');
            }

            if ($execution->completed_at && $occurredAt->gt($execution->completed_at)) {
                throw new InvalidArgumentException('Key time cannot exceed execution completion.');
            }

            if ($record->reference_seconds !== null && (int) $record->reference_seconds < 0) {
                throw new InvalidArgumentException('Key time reference cannot be negative.');
            }

            $record->setAttribute(
                'elapsed_seconds',
                (int) $execution->started_at->diffInSeconds($occurredAt),
            );
        });

        static::deleting(function (KeyTimeRecord $record): void {
            if ($record->assessment()->firstOrFail()->isFinalized()) {
                throw new LogicException('Finalized key time records are immutable.');
            }
        });
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ExecutionAssessment::class, 'execution_assessment_id');
    }
}
