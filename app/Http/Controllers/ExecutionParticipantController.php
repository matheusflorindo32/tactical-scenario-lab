<?php

namespace App\Http\Controllers;

use App\Models\ExecutionTeam;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\ScenarioExecution;
use App\Services\Auth\ActiveOrganization;
use App\Support\Auth\AccessAbility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExecutionParticipantController extends Controller
{
    public function store(
        Request $request,
        ScenarioExecution $execution,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $organizationId = $activeOrganization->ensureAbility($request, AccessAbility::SCENARIOS_MANAGE);
        abort_unless($execution->organization_id === $organizationId, 403);
        abort_unless($execution->canConfigure(), 409, 'A execução encerrada não pode receber participantes.');

        $validated = $request->validate([
            'person_id' => [
                'required',
                'integer',
                'exists:people,id',
                Rule::unique('execution_participants', 'person_id')
                    ->where(fn ($query) => $query->where('scenario_execution_id', $execution->id)),
            ],
            'execution_team_id' => ['nullable', 'integer', 'exists:execution_teams,id'],
            'role' => ['nullable', 'string', 'max:80'],
        ]);

        if (isset($validated['execution_team_id'])) {
            $team = ExecutionTeam::query()->findOrFail($validated['execution_team_id']);
            abort_unless($team->scenario_execution_id === $execution->id, 403);
        }

        $person = Person::query()->findOrFail($validated['person_id']);
        $hasActiveMembership = $person->isActive() && OrganizationMembership::query()
            ->where('person_id', $person->id)
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->exists();

        abort_unless($hasActiveMembership, 403, 'A pessoa não possui vínculo institucional ativo nesta organização.');

        $execution->participants()->create($validated);

        return back()->with('success', 'Participante adicionado à execução.');
    }
}
