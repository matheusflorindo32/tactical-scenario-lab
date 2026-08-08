<?php

namespace App\Reporting;

use App\Models\ActionItem;
use App\Models\ExecutionAssessment;
use App\Models\ScenarioExecution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class InstructorDashboardQuery
{
    public function get(InstitutionalFilter $filter): array
    {
        $executions = $this->executions($filter);
        $assessments = $this->assessments($filter);
        $actions = $this->actions($filter);

        return [
            'running_count' => (clone $executions)->where('status', 'running')->count(),
            'draft_execution_count' => (clone $executions)->where('status', 'draft')->count(),
            'completed_without_assessment_count' => (clone $executions)
                ->where('status', 'completed')
                ->whereDoesntHave('assessment')
                ->count(),
            'draft_assessment_count' => (clone $assessments)->where('status', 'draft')->count(),
            'open_action_count' => (clone $actions)->whereIn('status', ['open', 'in_progress'])->count(),
            'overdue_action_count' => (clone $actions)
                ->whereIn('status', ['open', 'in_progress'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'running_executions' => $this->executionList((clone $executions)->where('status', 'running'), 6),
            'completed_without_assessment' => $this->executionList(
                (clone $executions)->where('status', 'completed')->whereDoesntHave('assessment'),
                6,
            ),
            'draft_assessments' => (clone $assessments)
                ->where('status', 'draft')
                ->with('execution.scenarioVersion.scenario')
                ->latest('updated_at')
                ->limit(6)
                ->get(),
            'actions_due_soon' => (clone $actions)
                ->whereIn('status', ['open', 'in_progress'])
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(14)->toDateString()])
                ->with('debrief.assessment.execution.scenarioVersion.scenario')
                ->orderBy('due_date')
                ->limit(8)
                ->get(),
            'recent_finalized_assessments' => (clone $assessments)
                ->where('status', 'finalized')
                ->with('execution.scenarioVersion.scenario')
                ->latest('finalized_at')
                ->limit(6)
                ->get(),
        ];
    }

    private function executions(InstitutionalFilter $filter): Builder
    {
        $query = ScenarioExecution::query()
            ->where('organization_id', $filter->organizationId)
            ->whereBetween(
                DB::raw('COALESCE(started_at, created_at)'),
                [$filter->dateFrom, $filter->dateTo],
            );

        if ($filter->scenarioId !== null) {
            $query->whereHas('scenarioVersion', fn (Builder $version) => $version->where('scenario_id', $filter->scenarioId));
        }

        if ($filter->unitId !== null) {
            $query->whereHas('participants', fn (Builder $participant) => $participant->where('unit_id_snapshot', $filter->unitId));
        }

        if ($filter->status !== null) {
            $query->where('status', $filter->status);
        }

        return $query;
    }

    private function assessments(InstitutionalFilter $filter): Builder
    {
        return ExecutionAssessment::query()
            ->where('organization_id', $filter->organizationId)
            ->whereHas('execution', fn (Builder $execution) => $this->applyExecutionConstraints($execution, $filter));
    }

    private function actions(InstitutionalFilter $filter): Builder
    {
        return ActionItem::query()
            ->whereHas('debrief.assessment.execution', fn (Builder $execution) => $this->applyExecutionConstraints($execution, $filter));
    }

    private function applyExecutionConstraints(Builder $query, InstitutionalFilter $filter): void
    {
        $query
            ->where('scenario_executions.organization_id', $filter->organizationId)
            ->whereBetween(
                DB::raw('COALESCE(scenario_executions.started_at, scenario_executions.created_at)'),
                [$filter->dateFrom, $filter->dateTo],
            );

        if ($filter->scenarioId !== null) {
            $query->whereHas('scenarioVersion', fn (Builder $version) => $version->where('scenario_id', $filter->scenarioId));
        }

        if ($filter->unitId !== null) {
            $query->whereHas('participants', fn (Builder $participant) => $participant->where('unit_id_snapshot', $filter->unitId));
        }
    }

    private function executionList(Builder $query, int $limit): Collection
    {
        return $query
            ->with('scenarioVersion.scenario')
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }
}
