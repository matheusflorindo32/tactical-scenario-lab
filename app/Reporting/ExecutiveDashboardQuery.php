<?php

namespace App\Reporting;

use App\Models\ActionItem;
use App\Models\CriticalErrorOccurrence;
use App\Models\ExecutionAssessment;
use App\Models\ScenarioExecution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ExecutiveDashboardQuery
{
    public function get(InstitutionalFilter $filter): array
    {
        $executions = $this->executions($filter);
        $assessments = $this->assessments($filter)->where('status', 'finalized');
        $actions = $this->actions($filter);

        $average = (clone $assessments)->whereNotNull('final_score')->avg('final_score');
        $knownResultCount = (clone $assessments)->whereNotNull('result')->count();
        $passedCount = $knownResultCount > 0
            ? (clone $assessments)->where('result', 'passed')->count()
            : 0;

        return [
            'total_executions' => (clone $executions)->count(),
            'completed_executions' => (clone $executions)->where('status', 'completed')->count(),
            'finalized_assessments' => (clone $assessments)->count(),
            'average_final_score' => $average === null ? null : round((float) $average, 2),
            'pass_rate' => $knownResultCount === 0 ? null : round(($passedCount / $knownResultCount) * 100, 2),
            'automatic_fail_count' => (clone $assessments)->where('automatic_fail', true)->count(),
            'top_observed_errors' => $this->topObservedErrors($filter),
            'open_action_count' => (clone $actions)->whereIn('status', ['open', 'in_progress'])->count(),
            'overdue_action_count' => (clone $actions)
                ->whereIn('status', ['open', 'in_progress'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'monthly_trend' => $this->monthlyTrend($filter),
        ];
    }

    private function executions(InstitutionalFilter $filter): Builder
    {
        $query = ScenarioExecution::query();
        $this->applyExecutionConstraints($query, $filter);

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

    private function topObservedErrors(InstitutionalFilter $filter): Collection
    {
        return CriticalErrorOccurrence::query()
            ->whereHas('assessment.execution', fn (Builder $execution) => $this->applyExecutionConstraints($execution, $filter))
            ->select('catalog_label_snapshot', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('catalog_label_snapshot')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get()
            ->mapWithKeys(fn (CriticalErrorOccurrence $row) => [
                $row->catalog_label_snapshot => (int) $row->getAttribute('aggregate'),
            ]);
    }

    private function monthlyTrend(InstitutionalFilter $filter): Collection
    {
        return $this->executions($filter)
            ->select(['id', 'started_at', 'created_at'])
            ->orderBy('id')
            ->cursor()
            ->reduce(function (Collection $months, ScenarioExecution $execution): Collection {
                $month = ($execution->started_at ?? $execution->created_at)->format('Y-m');
                $months->put($month, ((int) $months->get($month, 0)) + 1);

                return $months;
            }, collect())
            ->sortKeys();
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
}
