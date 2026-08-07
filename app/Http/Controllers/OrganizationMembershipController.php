<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationMembershipRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizationMembershipController extends Controller
{
    public function create(Person $person): View
    {
        $organizations = Organization::query()
            ->where('status', 'active')
            ->with(['units' => fn ($query) => $query->where('status', 'active')->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('people.memberships.create', compact('person', 'organizations'));
    }

    public function store(StoreOrganizationMembershipRequest $request, Person $person): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureUnitBelongsToOrganization($data['unit_id'] ?? null, (int) $data['organization_id']);
        $this->ensureNoEquivalentActiveMembership($person, (int) $data['organization_id'], $data['unit_id'] ?? null);

        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $data['organization_id'],
            'unit_id' => $data['unit_id'] ?? null,
            'position' => $data['position'] ?? null,
            'started_at' => $data['started_at'] ?? null,
            'ended_at' => $data['ended_at'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Novo vínculo institucional adicionado.');
    }

    private function ensureUnitBelongsToOrganization(?int $unitId, int $organizationId): void
    {
        if ($unitId === null) {
            return;
        }

        $valid = Unit::query()
            ->whereKey($unitId)
            ->where('organization_id', $organizationId)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'unit_id' => 'A unidade selecionada não pertence à organização informada.',
            ]);
        }
    }

    private function ensureNoEquivalentActiveMembership(Person $person, int $organizationId, ?int $unitId): void
    {
        $exists = $person->memberships()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->when($unitId === null, fn ($query) => $query->whereNull('unit_id'))
            ->when($unitId !== null, fn ($query) => $query->where('unit_id', $unitId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'organization_id' => 'Já existe um vínculo ativo equivalente para esta pessoa.',
            ]);
        }
    }
}
