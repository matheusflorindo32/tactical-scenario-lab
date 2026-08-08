<?php

namespace App\Services;

use App\Models\ExecutionAssessment;
use App\Models\ScenarioExecution;
use Illuminate\Support\Facades\DB;
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

    public function calculator(): AssessmentScoreCalculator
    {
        return $this->calculator;
    }
}
