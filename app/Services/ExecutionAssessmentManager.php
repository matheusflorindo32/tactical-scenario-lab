<?php

namespace App\Services;

use App\Models\ExecutionAssessment;
use App\Models\ScenarioExecution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class ExecutionAssessmentManager
{
    public function __construct(private readonly AssessmentScoreCalculator $calculator) {}

    public function createForExecution(ScenarioExecution $execution): ExecutionAssessment
    {
        return DB::transaction(function () use ($execution): ExecutionAssessment {
            $lockedExecution = ScenarioExecution::query()
                ->with('scenarioVersion')
                ->lockForUpdate()
                ->findOrFail($execution->id);

            if ($lockedExecution->assessment()->exists()) {
                throw new LogicException('Execution already has an assessment.');
            }

            $assessment = ExecutionAssessment::create([
                'organization_id' => $lockedExecution->organization_id,
                'scenario_execution_id' => $lockedExecution->id,
                'source' => 'm4',
                'status' => 'draft',
                'pass_threshold' => 70.00,
            ]);

            $objectives = collect($lockedExecution->scenarioVersion->learning_objectives ?? [])
                ->filter(fn ($objective): bool => is_string($objective) && trim($objective) !== '')
                ->map(fn (string $objective): string => trim($objective))
                ->values();

            $count = $objectives->count();

            if ($count > 0) {
                $totalHundredths = 10000;
                $baseHundredths = intdiv($totalHundredths, $count);
                $remainder = $totalHundredths - ($baseHundredths * $count);

                $objectives->each(function (string $objective, int $index) use ($assessment, $count, $baseHundredths, $remainder): void {
                    $hundredths = $baseHundredths + ($index === $count - 1 ? $remainder : 0);

                    $assessment->criteria()->create([
                        'label' => $objective,
                        'weight' => $hundredths / 100,
                        'position' => $index + 1,
                    ]);
                });
            }

            return $assessment->fresh('criteria');
        });
    }

    public function setAdjustment(
        ExecutionAssessment $assessment,
        int $adjustment,
        ?string $justification,
    ): ExecutionAssessment {
        if ($assessment->isFinalized()) {
            throw new LogicException('Finalized assessment content is immutable.');
        }

        if ($adjustment < -10 || $adjustment > 10) {
            throw new InvalidArgumentException('Evaluator adjustment must be between -10 and 10.');
        }

        $justification = $justification !== null ? trim($justification) : null;

        if ($adjustment !== 0 && blank($justification)) {
            throw new InvalidArgumentException('A nonzero evaluator adjustment requires justification.');
        }

        $assessment->update([
            'evaluator_adjustment' => $adjustment,
            'adjustment_justification' => $adjustment === 0 ? null : $justification,
        ]);

        return $assessment->fresh();
    }

    public function finalize(ExecutionAssessment $assessment, User $evaluator): ExecutionAssessment
    {
        return DB::transaction(function () use ($assessment, $evaluator): ExecutionAssessment {
            $locked = ExecutionAssessment::query()
                ->with([
                    'execution.scenarioVersion',
                    'criteria.evidence',
                    'criticalErrorOccurrences',
                    'debrief.entries',
                ])
                ->lockForUpdate()
                ->findOrFail($assessment->id);

            if ($locked->isFinalized()) {
                throw new LogicException('Assessment is already finalized.');
            }

            if ($locked->source !== 'm4') {
                throw new LogicException('Legacy assessment snapshots cannot use normal M4 finalization.');
            }

            if (! $locked->execution->isCompleted()) {
                throw new LogicException('Only a completed execution can be finalized.');
            }

            if ($locked->criteria->isEmpty()) {
                throw new LogicException('At least one assessment criterion is required.');
            }

            if ($locked->criteria->contains(fn ($criterion): bool => (float) $criterion->weight <= 0)) {
                throw new LogicException('Every criterion must have a positive weight.');
            }

            if (round((float) $locked->criteria->sum('weight'), 2) !== 100.0) {
                throw new LogicException('Criterion weights must total exactly 100.00.');
            }

            if ($locked->criteria->contains(
                fn ($criterion): bool => $criterion->score === null
                    || (float) $criterion->score < 0
                    || (float) $criterion->score > 100,
            )) {
                throw new LogicException('Every criterion must have a score between 0 and 100.');
            }

            if ($locked->criteria->contains(fn ($criterion): bool => $criterion->evidence->isEmpty())) {
                throw new LogicException('Every criterion requires evidence.');
            }

            $kinds = $locked->debrief?->entries?->pluck('kind')->all() ?? [];

            foreach (['fact', 'interpretation', 'recommendation'] as $requiredKind) {
                if (! in_array($requiredKind, $kinds, true)) {
                    throw new LogicException('Structured debrief requires fact, interpretation and recommendation.');
                }
            }

            if ($locked->evaluator_adjustment < -10 || $locked->evaluator_adjustment > 10) {
                throw new LogicException('Evaluator adjustment is outside the allowed range.');
            }

            if ($locked->evaluator_adjustment !== 0 && blank($locked->adjustment_justification)) {
                throw new LogicException('Nonzero evaluator adjustment requires justification.');
            }

            if ($locked->pass_threshold === null) {
                throw new LogicException('M4 assessment requires an explicit pass threshold.');
            }

            $base = $this->calculator->calculateBase($locked->criteria);
            $penalties = round((float) $locked->criticalErrorOccurrences
                ->where('rule', 'penalty')
                ->sum('penalty_points'), 2);
            $automaticFail = $locked->criticalErrorOccurrences->contains('rule', 'automatic_fail');
            $final = $this->calculator->calculateFinal($base, $penalties, $locked->evaluator_adjustment);
            $result = $this->calculator->result($final, (float) $locked->pass_threshold, $automaticFail);

            $locked->update([
                'base_score' => $base,
                'penalty_points' => $penalties,
                'automatic_fail' => $automaticFail,
                'final_score' => $final,
                'result' => $result,
                'status' => 'finalized',
                'finalized_at' => now(),
                'finalized_by_user_id' => $evaluator->id,
            ]);

            return $locked->fresh();
        });
    }
}
