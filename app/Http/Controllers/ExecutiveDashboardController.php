<?php

namespace App\Http\Controllers;

use App\Reporting\ExecutiveDashboardQuery;
use App\Reporting\InstitutionalFilter;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutiveDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ActiveOrganization $activeOrganization,
        ExecutiveDashboardQuery $query,
    ): View {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::REPORTS_VIEW);
        $filter = InstitutionalFilter::fromRequest(
            $request,
            $organizationId,
            ['draft', 'running', 'completed', 'cancelled'],
        );

        return view('dashboard.executive', [
            ...$query->get($filter),
            'filter' => $filter,
        ]);
    }
}
