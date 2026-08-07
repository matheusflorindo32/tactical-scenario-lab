<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\UserOrganizationAccess;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessAdministrationController extends Controller
{
    public function index(Request $request, ActiveOrganization $activeOrganization): View
    {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::ACCESS_MANAGE);
        $organization = Organization::query()->findOrFail($organizationId);

        $accesses = UserOrganizationAccess::query()
            ->where('organization_id', $organizationId)
            ->whereNull('revoked_at')
            ->with('user:id,name,email,status')
            ->orderBy('role')
            ->orderBy('user_id')
            ->paginate(20);

        return view('access.index', [
            'organization' => $organization,
            'accesses' => $accesses,
            'abilityLabels' => AccessAbility::labels(),
        ]);
    }
}
