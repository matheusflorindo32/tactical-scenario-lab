<?php

namespace App\Http\Controllers;

use App\Reporting\InstitutionalFilter;
use App\Reporting\InstructorDashboardQuery;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ActiveOrganization $activeOrganization,
        InstructorDashboardQuery $query,
    ): View {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_VIEW);
        $filter = InstitutionalFilter::fromRequest(
            $request,
            $organizationId,
            ['draft', 'running', 'completed', 'cancelled'],
        );
        $access = $request->user()
            ->activeOrganizationAccesses()
            ->where('organization_id', $organizationId)
            ->first();
        $abilities = $access?->abilities ?? [];

        return view('dashboard', [
            ...$query->get($filter),
            'filter' => $filter,
            'canManageScenarios' => in_array(AccessAbility::SCENARIOS_MANAGE, $abilities, true),
            'canViewReports' => in_array(AccessAbility::REPORTS_VIEW, $abilities, true),
        ]);
    }
}
