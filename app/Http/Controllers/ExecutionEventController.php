<?php

namespace App\Http\Controllers;

use App\Models\ExecutionParticipant;
use App\Models\ExecutionTeam;
use App\Models\ScenarioExecution;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExecutionEventController extends Controller
{
    private const KINDS = [
        'observation',
        'action',
        'intervention',
        'system',
        'inject',
        'resource',
    ];

    public function store(
        Request $request,
        ScenarioExecution $execution,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        abort_unless($execution->organization_id === $organizationId, 403);
        abort_unless($execution->isRunning(), 409, 'A timeline só pode receber eventos durante uma execução em andamento.');

        $validated = $request->validate([
            'execution_team_id' => ['nullable', 'integer', 'exists:execution_teams,id'],
            'execution_participant_id' => ['nullable', 'integer', 'exists:execution_participants,id'],
            'kind' => ['required', 'string', Rule::in(self::KINDS)],
            'occurred_at' => ['required', 'date'],
            'summary' => ['required', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ]);

        if (isset($validated['execution_team_id'])) {
            $team = ExecutionTeam::query()->findOrFail($validated['execution_team_id']);
            abort_unless($team->scenario_execution_id === $execution->id, 403);
        }

        if (isset($validated['execution_participant_id'])) {
            $participant = ExecutionParticipant::query()->findOrFail($validated['execution_participant_id']);
            abort_unless($participant->scenario_execution_id === $execution->id, 403);
        }

        $execution->events()->create($validated);

        return back()->with('success', 'Evento registrado na timeline da execução.');
    }
}
