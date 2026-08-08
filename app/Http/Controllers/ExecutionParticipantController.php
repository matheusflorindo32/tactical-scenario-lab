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
use Illuminate\Validation\ValidationException;

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
            'person_uuid' => ['nullable', 'uuid', 'exists:people,uuid', 'required_without:person_id'],
            'person_id' => ['nullable', 'integer', 'exists:people,id', 'required_without:person_uuid'],
            'execution_team_uuid' => ['nullable', 'uuid', 'exists:execution_teams,uuid'],
            'execution_team_id' => ['nullable', 'integer', 'exists:execution_teams,id'],
            'role' => ['nullable', 'string', 'max:80'],
        ]);

        $person = isset($validated['person_uuid'])
            ? Person::query()->where('uuid', $validated['person_uuid'])->firstOrFail()
            : Person::query()->findOrFail($validated['person_id']);

        $duplicateField = isset($validated['person_uuid']) ? 'person_uuid' : 'person_id';
        if ($execution->participants()->where('person_id', $person->id)->exists()) {
            throw ValidationException::withMessages([
                $duplicateField => 'Esta pessoa já participa desta execução.',
            ]);
        }

        $team = null;
        if (isset($validated['execution_team_uuid'])) {
            $team = ExecutionTeam::query()->where('uuid', $validated['execution_team_uuid'])->firstOrFail();
        } elseif (isset($validated['execution_team_id'])) {
            $team = ExecutionTeam::query()->findOrFail($validated['execution_team_id']);
        }

        if ($team) {
            abort_unless($team->scenario_execution_id === $execution->id, 403);
        }

        $hasActiveMembership = $person->isActive() && OrganizationMembership::query()
            ->where('person_id', $person->id)
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->exists();

        abort_unless($hasActiveMembership, 403, 'A pessoa não possui vínculo institucional ativo nesta organização.');

        $execution->participants()->create([
            'person_id' => $person->id,
            'execution_team_id' => $team?->id,
            'role' => $validated['role'] ?? null,
        ]);

        return back()->with('success', 'Participante adicionado à execução.');
    }
}
