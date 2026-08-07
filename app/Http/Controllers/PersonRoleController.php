<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRoleRequest;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonRole;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\ActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersonRoleController extends Controller
{
    public function create(Request $request, Person $person, ActiveOrganization $activeOrganization): View
    {
        $organizationId = $activeOrganization->ensurePerson($request, $person, true);

        return view('people.roles.create', [
            'person' => $person,
            'organizations' => Organization::query()
                ->whereKey($organizationId)
                ->where('status', 'active')
                ->get(),
            'roleOptions' => StorePersonRoleRequest::ROLE_OPTIONS,
            'abilityOptions' => StorePersonRoleRequest::ABILITY_OPTIONS,
        ]);
    }

    public function store(
        StorePersonRoleRequest $request,
        Person $person,
        AuditLogger $audit,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $data = $request->validated();
        $organizationId = $activeOrganization->ensurePerson($request, $person, true);
        $activeOrganization->ensure($request, (int) $data['organization_id']);

        $hasMembership = $person->memberships()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->exists();

        if (! $hasMembership) {
            throw ValidationException::withMessages([
                'organization_id' => 'A pessoa precisa ter vínculo ativo com a organização selecionada.',
            ]);
        }

        $alreadyActive = PersonRole::query()
            ->where('person_id', $person->id)
            ->where('organization_id', $organizationId)
            ->where('role', $data['role'])
            ->whereNull('revoked_at')
            ->exists();

        if ($alreadyActive) {
            throw ValidationException::withMessages([
                'role' => 'Esta pessoa já possui esse papel ativo na organização selecionada.',
            ]);
        }

        $role = PersonRole::create([
            'person_id' => $person->id,
            'organization_id' => $organizationId,
            'role' => $data['role'],
            'abilities' => $data['abilities'] ?? [],
            'granted_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        $audit->record('person_role.granted', $role, $organizationId, [
            'person_id' => $person->id,
            'role' => $role->role,
            'abilities' => $role->abilities ?? [],
        ], $request);

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Papel institucional atribuído com sucesso.');
    }

    public function revoke(
        Request $request,
        Person $person,
        PersonRole $role,
        AuditLogger $audit,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $activeOrganization->ensurePerson($request, $person);
        abort_unless($role->person_id === $person->id, 404);
        $activeOrganization->ensure($request, $role->organization_id);

        if ($role->revoked_at === null) {
            $role->update(['revoked_at' => now()]);

            $audit->record('person_role.revoked', $role, $role->organization_id, [
                'person_id' => $person->id,
                'role' => $role->role,
            ], $request);
        }

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Papel institucional revogado e mantido no histórico.');
    }
}
