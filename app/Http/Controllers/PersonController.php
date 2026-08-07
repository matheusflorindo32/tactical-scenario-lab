<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\PersonIdentifier;
use App\Models\PersonRole;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function index(): View
    {
        $search = trim((string) request('q'));
        $status = request('status');
        $organizationId = request('organization_id');

        $people = Person::query()
            ->with([
                'memberships.organization',
                'memberships.unit',
                'roles',
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('display_name', 'like', "%{$search}%")
                        ->orWhere('social_name', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'incomplete', 'inactive', 'merged'], true), fn (Builder $query) => $query->where('status', $status))
            ->when($organizationId, function (Builder $query) use ($organizationId): void {
                $query->whereHas('memberships', fn (Builder $membership) => $membership->where('organization_id', $organizationId));
            })
            ->orderBy('display_name')
            ->paginate(15)
            ->withQueryString();

        $organizations = Organization::query()->where('status', 'active')->orderBy('name')->get();

        return view('people.index', compact('people', 'organizations', 'search', 'status', 'organizationId'));
    }

    public function create(): View
    {
        $organizations = Organization::query()
            ->where('status', 'active')
            ->with(['units' => fn ($query) => $query->where('status', 'active')->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('people.create', compact('organizations'));
    }

    public function store(StorePersonRequest $request): RedirectResponse
    {
        $data = $request->validated();
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

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Pessoa cadastrada. Documentos e contatos podem ser completados depois.');
    }

    public function show(Person $person): View
    {
        $person->load([
            'identifiers' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('type'),
            'contacts' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('type'),
            'memberships.organization',
            'memberships.unit',
            'roles.organization',
        ]);

        return view('people.show', compact('person'));
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

    private function generateTemporaryCode(): string
    {
        do {
            $code = 'TMA-'.Str::upper(Str::random(8));
            $fingerprint = PersonIdentifier::fingerprintFor('temp_code', $code);
        } while (PersonIdentifier::query()->where('value_fingerprint', $fingerprint)->exists());

        return $code;
    }
}
