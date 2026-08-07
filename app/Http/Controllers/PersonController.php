<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\PersonIdentifier;
use App\Models\PersonRole;
use App\Models\Unit;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\ActiveOrganization;
use App\Services\People\PersonSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function index(Request $request, PersonSearch $personSearch, ActiveOrganization $activeOrganization): View
    {
        $search = trim((string) $request->query('q'));
        $status = $request->query('status');
        $organizationId = $activeOrganization->id($request);

        $query = Person::query()
            ->whereHas('memberships', fn (Builder $membership) => $membership->where('organization_id', $organizationId))
            ->with([
                'memberships' => fn ($membership) => $membership->where('organization_id', $organizationId),
                'memberships.organization',
                'memberships.unit',
                'roles' => fn ($role) => $role->where('organization_id', $organizationId),
            ]);

        $personSearch->apply($query, $search, $organizationId);

        $people = $query
            ->when(in_array($status, ['active', 'incomplete', 'inactive', 'merged'], true), fn (Builder $builder) => $builder->where('status', $status))
            ->orderBy('display_name')
            ->paginate(15)
            ->withQueryString();

        $organizations = Organization::query()->whereKey($organizationId)->get();

        return view('people.index', compact('people', 'organizations', 'search', 'status', 'organizationId'));
    }

    public function create(Request $request, ActiveOrganization $activeOrganization): View
    {
        $organizationId = $activeOrganization->id($request);
        $organizations = Organization::query()
            ->whereKey($organizationId)
            ->where('status', 'active')
            ->with(['units' => fn ($query) => $query->where('status', 'active')->orderBy('name')])
            ->get();

        return view('people.create', compact('organizations'));
    }

    public function store(StorePersonRequest $request, ActiveOrganization $activeOrganization): RedirectResponse
    {
        $data = $request->validated();
        $activeOrganization->ensure($request, (int) $data['organization_id']);
        $this->ensureUnitBelongsToOrganization($data['unit_id'] ?? null, (int) $data['organization_id']);

        $person = DB::transaction(function () use ($data): Person {
            $person = Person::create([
                'display_name' => $data['display_name'],
                'social_name' => $data['social_name'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'status' => 'incomplete',
                'notes' => $data['notes'] ?? null,
            ]);

            OrganizationMembership::create([
                'person_id' => $person->id,
                'organization_id' => $data['organization_id'],
                'unit_id' => $data['unit_id'] ?? null,
                'position' => $data['position'] ?? null,
                'status' => 'active',
                'started_at' => now()->toDateString(),
            ]);

            PersonRole::create([
                'person_id' => $person->id,
                'organization_id' => $data['organization_id'],
                'role' => $data['role'],
                'granted_at' => now(),
            ]);

            PersonIdentifier::create([
                'person_id' => $person->id,
                'organization_id' => $data['organization_id'],
                'type' => 'temp_code',
                'value' => $this->generateTemporaryCode(),
                'is_primary' => true,
            ]);

            return $person;
        });

        app(AuditLogger::class)->record(
            'person.created',
            $person,
            (int) $data['organization_id'],
            ['status' => $person->status, 'initial_role' => $data['role']],
            $request,
        );

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Pessoa cadastrada. Documentos e contatos podem ser completados depois.');
    }

    public function show(Request $request, Person $person, ActiveOrganization $activeOrganization): View
    {
        $organizationId = $this->ensurePersonInActiveOrganization($request, $person, $activeOrganization);

        $person->load([
            'identifiers' => fn ($query) => $query->where('organization_id', $organizationId)->orderByDesc('is_primary')->orderBy('type'),
            'contacts' => fn ($query) => $query->where('organization_id', $organizationId)->orderByDesc('is_primary')->orderBy('type'),
            'memberships' => fn ($query) => $query->where('organization_id', $organizationId),
            'memberships.organization',
            'memberships.unit',
            'roles' => fn ($query) => $query->where('organization_id', $organizationId),
            'roles.organization',
        ]);

        return view('people.show', compact('person'));
    }

    public function edit(Request $request, Person $person, ActiveOrganization $activeOrganization): View
    {
        $this->ensurePersonInActiveOrganization($request, $person, $activeOrganization);

        return view('people.edit', compact('person'));
    }

    public function update(
        UpdatePersonRequest $request,
        Person $person,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $organizationId = $this->ensurePersonInActiveOrganization($request, $person, $activeOrganization);
        $before = $person->only(['display_name', 'social_name', 'birth_date', 'status', 'notes']);
        $person->update($request->validated());

        $changedFields = collect($person->getChanges())
            ->keys()
            ->reject(fn (string $field) => $field === 'updated_at')
            ->values()
            ->all();

        app(AuditLogger::class)->record(
            'person.updated',
            $person,
            $organizationId,
            [
                'changed_fields' => $changedFields,
                'previous_status' => $before['status'],
                'current_status' => $person->status,
            ],
            $request,
        );

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Cadastro atualizado com sucesso.');
    }

    public function deactivate(
        Request $request,
        Person $person,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $organizationId = $this->ensurePersonInActiveOrganization($request, $person, $activeOrganization);

        if ($person->status !== 'inactive') {
            $previousStatus = $person->status;
            $person->update(['status' => 'inactive']);

            app(AuditLogger::class)->record(
                'person.deactivated',
                $person,
                $organizationId,
                ['previous_status' => $previousStatus, 'current_status' => 'inactive'],
                $request,
            );
        }

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Pessoa inativada sem exclusão do histórico.');
    }

    private function ensurePersonInActiveOrganization(
        Request $request,
        Person $person,
        ActiveOrganization $activeOrganization,
    ): int {
        $organizationId = $activeOrganization->id($request);

        abort_unless(
            $person->memberships()->where('organization_id', $organizationId)->exists(),
            403,
            'A pessoa solicitada não pertence à organização ativa.',
        );

        return $organizationId;
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
                'unit_id' => 'A unidade selecionada não pertence à organização informada ou está inativa.',
            ]);
        }
    }

    private function generateTemporaryCode(): string
    {
        do {
            $code = 'TMA-'.Str::upper(Str::random(8));
            $fingerprint = PersonIdentifier::fingerprintFor('temp_code', $code);
        } while (PersonIdentifier::query()->where('value_fingerprint', $fingerprint)->exists());

        return $code;
    }
}
