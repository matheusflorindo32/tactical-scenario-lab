<?php

namespace App\Http\Controllers;

use App\Reporting\ExecutionHistoryQuery;
use App\Reporting\InstitutionalFilter;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutionHistoryController extends Controller
{
    public function __invoke(
        Request $request,
        ActiveOrganization $activeOrganization,
        ExecutionHistoryQuery $query,
    ): View {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::REPORTS_VIEW);
        $filter = InstitutionalFilter::fromRequest(
            $request,
            $organizationId,
            ['draft', 'running', 'completed', 'cancelled'],
        );

        return view('history.executions', [
            'executions' => $query->paginate($filter),
            'filter' => $filter,
        ]);
    }
}
