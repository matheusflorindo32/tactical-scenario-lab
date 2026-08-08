<?php

namespace App\Http\Controllers;

use App\Models\ScenarioVersion;
use App\Services\Auth\ActiveOrganization;
use App\Services\ScenarioVersionManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScenarioVersionController extends Controller
{
    public function publish(
        Request $request,
        ScenarioVersion $scenarioVersion,
        ActiveOrganization $activeOrganization,
        ScenarioVersionManager $manager,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        $scenario = $scenarioVersion->scenario()->firstOrFail();

        abort_unless(
            $scenario->organization_id === $organizationId,
            403,
            'A versão solicitada pertence a outra organização.',
        );

        $manager->publish($scenarioVersion);

        return back()->with('success', 'Versão publicada e pronta para novas execuções.');
    }
}
