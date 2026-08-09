<?php

namespace App\Reporting;

use App\Models\ScenarioExecution;
use InvalidArgumentException;

final class ExecutionReportDataBuilder
{
    public function build(ScenarioExecution $execution, int $organizationId): array
    {
        if ((int) $execution->organization_id !== $organizationId) {
            throw new InvalidArgumentException('Execution does not belong to the active organization.');
        }

        $execution->load([
            'organization',
            'scenarioVersion.scenario',
            'teams',
            'participants.person',
            'participants.team',
            'assessment.criteria.evidence',
            'assessment.criticalErrorOccurrences',
            'assessment.keyTimes',
            'assessment.debrief.entries',
            'assessment.debrief.actionItems.responsiblePerson',
        ]);

        $assessment = $execution->assessment;
        $scenario = $execution->scenarioVersion->scenario;

        return [
            'organization' => [
                'name' => $execution->organization->name,
            ],
            'scenario' => [
                'uuid' => $scenario->uuid,
                'title' => $scenario->title,
                'version' => $execution->scenarioVersion->version_number,
                'environment' => $execution->scenarioVersion->environment,
                'threat_level' => $execution->scenarioVersion->threat_level,
                'mechanism' => $execution->scenarioVersion->mechanism,
                'estimated_casualty_count' => $execution->scenarioVersion->estimated_casualty_count,
            ],
            'execution' => [
                'uuid' => $execution->uuid,
                'sequence' => $execution->sequence_number,
                'status' => $execution->status,
                'started_at' => $execution->started_at?->toIso8601String(),
                'completed_at' => $execution->completed_at?->toIso8601String(),
            ],
            'teams' => $execution->teams->map(fn ($team) => [
                'label' => $team->label,
                'description' => $team->description,
            ])->values()->all(),
            'participants' => $execution->participants->map(fn ($participant) => [
                'name' => $participant->person->preferredName(),
                'role' => $participant->role,
                'team' => $participant->team?->label,
                'unit' => $participant->unit_name_snapshot ?: 'Sem unidade histórica',
                'position' => $participant->position_snapshot,
            ])->values()->all(),
            'assessment' => $assessment ? [
                'source' => $assessment->source,
                'status' => $assessment->status,
                'pass_threshold' => $assessment->pass_threshold,
                'base_score' => $assessment->base_score,
                'penalty_points' => $assessment->penalty_points,
                'evaluator_adjustment' => $assessment->evaluator_adjustment,
                'adjustment_justification' => $assessment->adjustment_justification,
                'final_score' => $assessment->final_score,
                'result' => $assessment->result,
                'automatic_fail' => $assessment->automatic_fail,
                'finalized_at' => $assessment->finalized_at?->toIso8601String(),
                'criteria' => $assessment->criteria->map(fn ($criterion) => [
                    'code' => $criterion->code,
                    'label' => $criterion->label,
                    'description' => $criterion->description,
                    'weight' => $criterion->weight,
                    'score' => $criterion->score,
                    'evaluator_notes' => $criterion->evaluator_notes,
                    'evidence' => $criterion->evidence->map(fn ($evidence) => [
                        'statement' => $evidence->statement,
                        'observed_at' => $evidence->observed_at?->toIso8601String(),
                    ])->values()->all(),
                ])->values()->all(),
                'critical_errors' => $assessment->criticalErrorOccurrences->map(fn ($occurrence) => [
                    'label' => $occurrence->catalog_label_snapshot,
                    'rule' => $occurrence->rule,
                    'penalty_points' => $occurrence->penalty_points,
                    'observed_at' => $occurrence->observed_at?->toIso8601String(),
                    'notes' => $occurrence->notes,
                ])->values()->all(),
                'key_times' => $assessment->keyTimes->map(fn ($keyTime) => [
                    'label' => $keyTime->label,
                    'occurred_at' => $keyTime->occurred_at?->toIso8601String(),
                    'elapsed_seconds' => $keyTime->elapsed_seconds,
                    'reference_seconds' => $keyTime->reference_seconds,
                    'notes' => $keyTime->notes,
                ])->values()->all(),
                'debrief' => $assessment->debrief ? [
                    'entries' => $assessment->debrief->entries->map(fn ($entry) => [
                        'kind' => $entry->kind,
                        'content' => $entry->content,
                    ])->values()->all(),
                    'actions' => $assessment->debrief->actionItems->map(fn ($action) => [
                        'action' => $action->action,
                        'responsible' => $action->responsiblePerson?->preferredName() ?: $action->responsible_label,
                        'due_date' => $action->due_date?->toDateString(),
                        'status' => $action->status,
                        'notes' => $action->notes,
                    ])->values()->all(),
                ] : null,
            ] : null,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
