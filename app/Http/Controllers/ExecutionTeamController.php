<?php

namespace App\Http\Controllers;

use App\Models\ScenarioExecution;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExecutionTeamController extends Controller
{
    public function store(
        Request $request,
        ScenarioExecution $execution,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        abort_unless($execution->organization_id === $organizationId, 403);
        abort_unless($execution->canConfigure(), 409, 'A execução encerrada não pode receber novas equipes.');

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $execution->teams()->create($validated);

        return back()->with('success', 'Equipe adicionada à execução.');
    }
}
