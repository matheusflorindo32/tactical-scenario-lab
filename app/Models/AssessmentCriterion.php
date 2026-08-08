<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class AssessmentCriterion extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'execution_assessment_id',
        'code',
        'label',
        'description',
        'weight',
        'score',
        'evaluator_notes',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'score' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AssessmentCriterion $criterion): void {
            if ($criterion->assessment()->firstOrFail()->isFinalized()) {
                throw new LogicException('Finalized assessment criteria are immutable.');
            }
        });

        static::deleting(function (AssessmentCriterion $criterion): void {
            if ($criterion->assessment()->firstOrFail()->isFinalized()) {
                throw new LogicException('Finalized assessment criteria are immutable.');
            }
        });
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ExecutionAssessment::class, 'execution_assessment_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(AssessmentEvidence::class)->orderBy('id');
    }
}
