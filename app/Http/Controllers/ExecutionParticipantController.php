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
            'person_uuid' => ['nullable', 'uuid', 'exists:people,uuid'],
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'organization_membership_uuid' => ['nullable', 'uuid', 'exists:organization_memberships,uuid'],
            'execution_team_uuid' => ['nullable', 'uuid', 'exists:execution_teams,uuid'],
            'execution_team_id' => ['nullable', 'integer', 'exists:execution_teams,id'],
            'role' => ['nullable', 'string', 'max:80'],
        ]);

        if (! isset($validated['person_uuid']) && ! isset($validated['person_id']) && ! isset($validated['organization_membership_uuid'])) {
            throw ValidationException::withMessages([
                'person_uuid' => 'Selecione uma pessoa ou vínculo institucional.',
            ]);
        }

        $explicitPerson = $this->resolveExplicitPerson($validated);
        $membership = $this->resolveMembership(
            $validated['organization_membership_uuid'] ?? null,
            $explicitPerson,
            $organizationId,
        );
        $person = $explicitPerson ?? $membership->person;

        abort_unless($person->isActive(), 403, 'A pessoa selecionada está inativa.');
        abort_unless($membership->person_id === $person->id, 403);
        abort_unless($membership->organization_id === $organizationId, 403);
        abort_unless($membership->isActive(), 403, 'O vínculo institucional selecionado não está ativo.');

        if ($membership->unit) {
            abort_unless($membership->unit->organization_id === $organizationId, 403);
        }

        $duplicateField = isset($validated['organization_membership_uuid'])
            ? 'organization_membership_uuid'
            : (isset($validated['person_uuid']) ? 'person_uuid' : 'person_id');

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

        $execution->participants()->create([
            'person_id' => $person->id,
            'organization_membership_id' => $membership->id,
            'unit_id_snapshot' => $membership->unit_id,
            'unit_name_snapshot' => $membership->unit?->name,
            'position_snapshot' => $membership->position,
            'execution_team_id' => $team?->id,
            'role' => $validated['role'] ?? null,
        ]);

        return back()->with('success', 'Participante adicionado à execução.');
    }

    private function resolveExplicitPerson(array $validated): ?Person
    {
        if (isset($validated['person_uuid'])) {
            return Person::query()->where('uuid', $validated['person_uuid'])->firstOrFail();
        }

        if (isset($validated['person_id'])) {
            return Person::query()->findOrFail($validated['person_id']);
        }

        return null;
    }

    private function resolveMembership(
        ?string $membershipUuid,
        ?Person $person,
        int $organizationId,
    ): OrganizationMembership {
        if ($membershipUuid) {
            $membership = OrganizationMembership::query()
                ->where('uuid', $membershipUuid)
                ->with(['person', 'unit'])
                ->firstOrFail();

            abort_unless($membership->organization_id === $organizationId, 403);
            if ($person) {
                abort_unless($membership->person_id === $person->id, 403);
            }

            return $membership;
        }

        abort_unless($person, 422, 'Selecione um vínculo institucional.');

        $memberships = OrganizationMembership::query()
            ->where('person_id', $person->id)
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->with(['person', 'unit'])
            ->get();

        abort_unless($memberships->isNotEmpty(), 403, 'A pessoa não possui vínculo institucional ativo nesta organização.');

        if ($memberships->count() !== 1) {
            throw ValidationException::withMessages([
                'organization_membership_uuid' => 'Esta pessoa possui mais de um vínculo ativo. Selecione explicitamente o vínculo representado nesta execução.',
            ]);
        }

        return $memberships->first();
    }
}
