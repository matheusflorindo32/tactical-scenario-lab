<?php

namespace App\Reporting;

use App\Models\ScenarioExecution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

final class ExecutionHistoryQuery
{
    public function paginate(InstitutionalFilter $filter, int $perPage = 25): LengthAwarePaginator
    {
        return $this->query($filter)
            ->orderByRaw('COALESCE(scenario_executions.started_at, scenario_executions.created_at) DESC')
            ->orderByDesc('scenario_executions.id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function cursor(InstitutionalFilter $filter): LazyCollection
    {
        return $this->query($filter)
            ->orderBy('scenario_executions.id')
            ->lazyById(200, column: 'scenario_executions.id', alias: 'id');
    }

    private function query(InstitutionalFilter $filter): Builder
    {
        $query = ScenarioExecution::query()
            ->where('scenario_executions.organization_id', $filter->organizationId)
            ->whereBetween(
                DB::raw('COALESCE(scenario_executions.started_at, scenario_executions.created_at)'),
                [$filter->dateFrom, $filter->dateTo],
            )
            ->with([
                'scenarioVersion.scenario',
                'assessment',
                'participants:id,scenario_execution_id,unit_id_snapshot,unit_name_snapshot',
            ])
            ->select('scenario_executions.*')
            ->selectSub(function ($subquery): void {
                $subquery
                    ->from('critical_error_occurrences')
                    ->join(
                        'execution_assessments',
                        'execution_assessments.id',
                        '=',
                        'critical_error_occurrences.execution_assessment_id',
                    )
                    ->whereColumn('execution_assessments.scenario_execution_id', 'scenario_executions.id')
                    ->selectRaw('COUNT(*)');
            }, 'critical_error_count')
            ->selectSub(function ($subquery): void {
                $subquery
                    ->from('action_items')
                    ->join('execution_debriefs', 'execution_debriefs.id', '=', 'action_items.execution_debrief_id')
                    ->join(
                        'execution_assessments',
                        'execution_assessments.id',
                        '=',
                        'execution_debriefs.execution_assessment_id',
                    )
                    ->whereColumn('execution_assessments.scenario_execution_id', 'scenario_executions.id')
                    ->whereIn('action_items.status', ['open', 'in_progress'])
                    ->selectRaw('COUNT(*)');
            }, 'open_action_count');

        if ($filter->scenarioId !== null) {
            $query->whereHas('scenarioVersion', fn (Builder $version) => $version->where('scenario_id', $filter->scenarioId));
        }

        if ($filter->unitId !== null) {
            $query->whereHas('participants', fn (Builder $participant) => $participant->where('unit_id_snapshot', $filter->unitId));
        }

        if ($filter->status !== null) {
            $query->where('scenario_executions.status', $filter->status);
        }

        return $query;
    }
}
