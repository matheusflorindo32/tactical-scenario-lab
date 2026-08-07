<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationMembershipRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\Unit;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\ActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizationMembershipController extends Controller
{
    public function create(Request $request, Person $person, ActiveOrganization $activeOrganization): View
    {
        $activeOrganization->ensurePerson($request, $person);

        $organizationIds = $request->user()
            ->activeOrganizationAccesses()
            ->pluck('organization_id');

        $organizations = Organization::query()
            ->whereIn('id', $organizationIds)
            ->where('status', 'active')
            ->with(['units' => fn ($query) => $query->where('status', 'active')->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('people.memberships.create', compact('person', 'organizations'));
    }

    public function store(
        StoreOrganizationMembershipRequest $request,
        Person $person,
        AuditLogger $audit,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $data = $request->validated();
        $activeOrganization->ensurePerson($request, $person);

        $organizationId = (int) $data['organization_id'];
        $activeOrganization->ensureAccess($request, $organizationId);
        $this->ensureOrganizationIsActive($organizationId);
        $this->ensureUnitBelongsToOrganization($data['unit_id'] ?? null, $organizationId);
        $this->ensureNoEquivalentActiveMembership($person, $organizationId, $data['unit_id'] ?? null);

        $membership = DB::transaction(function () use ($data, $person, $organizationId): OrganizationMembership {
            return OrganizationMembership::create([
                'person_id' => $person->id,
                'organization_id' => $organizationId,
                'unit_id' => $data['unit_id'] ?? null,
                'position' => $data['position'] ?? null,
                'started_at' => $data['started_at'] ?? null,
                'ended_at' => $data['ended_at'] ?? null,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $audit->record('organization_membership.created', $membership, $organizationId, [
            'person_id' => $person->id,
            'unit_id' => $membership->unit_id,
            'status' => $membership->status,
            'has_end_date' => filled($membership->ended_at),
        ], $request);

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Novo vínculo institucional adicionado.');
    }

    public function close(
        Request $request,
        Person $person,
        OrganizationMembership $membership,
        AuditLogger $audit,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        abort_unless($membership->person_id === $person->id, 404);

        $organizationId = $activeOrganization->ensurePerson($request, $person);
        $activeOrganization->ensure($request, $membership->organization_id);

        if ($membership->isActive()) {
            DB::transaction(function () use ($request, $person, $membership, $audit, $organizationId): void {
                $membership->update([
                    'status' => 'inactive',
                    'ended_at' => now()->toDateString(),
                ]);

                $hasAnotherActiveMembership = $person->memberships()
                    ->where('organization_id', $organizationId)
                    ->whereKeyNot($membership->id)
                    ->where('status', 'active')
                    ->whereNull('ended_at')
                    ->exists();

                $revokedRoles = 0;

                if (! $hasAnotherActiveMembership) {
                    $revokedRoles = $person->roles()
                        ->where('organization_id', $organizationId)
                        ->whereNull('revoked_at')
                        ->update(['revoked_at' => now()]);
                }

                $audit->record('organization_membership.closed', $membership, $organizationId, [
                    'person_id' => $person->id,
                    'unit_id' => $membership->unit_id,
                    'status' => 'inactive',
                    'roles_revoked' => $revokedRoles,
                ], $request);
            });
        }

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Vínculo encerrado sem apagar o histórico institucional.');
    }

    private function ensureOrganizationIsActive(int $organizationId): void
    {
        if (! Organization::query()->whereKey($organizationId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                'organization_id' => 'A organização selecionada precisa estar ativa.',
            ]);
        }
    }

    private function ensureUnitBelongsToOrganization(?int $unitId, int $organizationId): void
    {
        if ($unitId === null) {
            return;
        }

        $valid = Unit::query()
            ->whereKey($unitId)
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'unit_id' => 'A unidade selecionada precisa estar ativa e pertencer à organização atual.',
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
