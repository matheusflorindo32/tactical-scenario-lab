<?php

namespace App\Services;

use App\Models\ExecutionAssessment;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use Illuminate\Support\Facades\DB;

final class LegacyAssessmentImporter
{
    public function import(): void
    {
        Scenario::query()
            ->where(function ($query): void {
                $query->whereNotNull('score')
                    ->orWhereNotNull('debrief_notes')
                    ->orWhereNotNull('observed_critical_errors');
            })
            ->orderBy('id')
            ->chunkById(100, function ($scenarios): void {
                foreach ($scenarios as $scenario) {
                    if (! $this->hasLegacyAssessmentData($scenario)) {
                        continue;
                    }

                    $this->importScenario($scenario);
                }
            });
    }

    private function importScenario(Scenario $scenario): void
    {
        $execution = ScenarioExecution::query()
            ->where('sequence_number', 1)
            ->whereHas('scenarioVersion', fn ($query) => $query->where('scenario_id', $scenario->id))
            ->orderBy('id')
            ->first();

        if (! $execution || $execution->assessment()->exists()) {
            return;
        }

        DB::transaction(function () use ($scenario, $execution): void {
            $lockedExecution = ScenarioExecution::query()
                ->lockForUpdate()
                ->findOrFail($execution->id);

            if ($lockedExecution->assessment()->exists()) {
                return;
            }

            $assessment = ExecutionAssessment::create([
                'organization_id' => $lockedExecution->organization_id,
                'scenario_execution_id' => $lockedExecution->id,
                'source' => 'legacy',
                'status' => 'draft',
                'pass_threshold' => null,
                'evaluator_adjustment' => 0,
            ]);

            $referenceAt = $lockedExecution->completed_at
                ?? $lockedExecution->started_at
                ?? $scenario->completed_at
                ?? $scenario->started_at
                ?? now();

            if ($scenario->score !== null) {
                $criterion = $assessment->criteria()->create([
                    'label' => 'Avaliação legada importada',
                    'weight' => 100,
                    'score' => $scenario->score,
                    'position' => 1,
                ]);

                $criterion->evidence()->create([
                    'statement' => 'Valor numérico importado do registro de avaliação legado do cenário.',
                    'observed_at' => $referenceAt,
                    'created_by_user_id' => null,
                ]);
            }

            $legacyErrors = collect($scenario->observed_critical_errors ?? [])
                ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
                ->map(fn (string $value): string => trim($value))
                ->unique()
                ->values();

            foreach ($legacyErrors as $legacyError) {
                $assessment->criticalErrorOccurrences()->create([
                    'catalog_label_snapshot' => $legacyError,
                    'rule' => 'record',
                    'penalty_points' => 0,
                    'observed_at' => $referenceAt,
                    'source' => 'legacy',
                ]);
            }

            if (is_string($scenario->debrief_notes) && trim($scenario->debrief_notes) !== '') {
                $debrief = $assessment->debrief()->create();
                $debrief->entries()->create([
                    'kind' => 'legacy_unstructured',
                    'content' => trim($scenario->debrief_notes),
                    'position' => 1,
                    'created_by_user_id' => null,
                ]);
            }

            $score = $scenario->score !== null ? (float) $scenario->score : null;

            $assessment->update([
                'status' => 'finalized',
                'base_score' => $score,
                'penalty_points' => 0,
                'evaluator_adjustment' => 0,
                'adjustment_justification' => null,
                'final_score' => $score,
                'result' => null,
                'automatic_fail' => false,
                'finalized_at' => $lockedExecution->completed_at ?? $scenario->completed_at ?? now(),
                'finalized_by_user_id' => null,
                'legacy_imported_at' => now(),
            ]);
        });
    }

    private function hasLegacyAssessmentData(Scenario $scenario): bool
    {
        $hasDebrief = is_string($scenario->debrief_notes) && trim($scenario->debrief_notes) !== '';
        $hasObservedErrors = collect($scenario->observed_critical_errors ?? [])
            ->contains(fn ($value): bool => is_string($value) && trim($value) !== '');

        return $scenario->score !== null || $hasDebrief || $hasObservedErrors;
    }
}
