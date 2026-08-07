<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRoleRequest;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersonRoleController extends Controller
{
    public function create(Person $person): View
    {
        $organizationIds = $person->memberships()
            ->where('status', 'active')
            ->pluck('organization_id');

        $organizations = Organization::query()
            ->whereIn('id', $organizationIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('people.roles.create', compact('person', 'organizations'));
    }

    public function store(StorePersonRoleRequest $request, Person $person): RedirectResponse
    {
        $data = $request->validated();

        $hasMembership = $person->memberships()
            ->where('organization_id', $data['organization_id'])
            ->where('status', 'active')
            ->exists();

        if (! $hasMembership) {
            throw ValidationException::withMessages([
                'organization_id' => 'A pessoa precisa ter vínculo ativo com a organização selecionada.',
            ]);
        }

        $alreadyActive = PersonRole::query()
            ->where('person_id', $person->id)
            ->where('organization_id', $data['organization_id'])
            ->where('role', $data['role'])
            ->whereNull('revoked_at')
            ->exists();

        if ($alreadyActive) {
            throw ValidationException::withMessages([
                'role' => 'Esta pessoa já possui esse papel ativo na organização selecionada.',
            ]);
        }

        PersonRole::create([
            'person_id' => $person->id,
            'organization_id' => $data['organization_id'],
            'role' => $data['role'],
            'abilities' => array_values(array_unique($data['abilities'] ?? [])),
            'granted_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Papel institucional atribuído com sucesso.');
    }

    public function revoke(Person $person, PersonRole $role): RedirectResponse
    {
        abort_unless($role->person_id === $person->id, 404);

        if ($role->revoked_at === null) {
            $role->update(['revoked_at' => now()]);
        }

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Papel institucional revogado e mantido no histórico.');
    }
}
