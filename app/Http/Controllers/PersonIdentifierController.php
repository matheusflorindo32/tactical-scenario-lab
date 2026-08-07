<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonIdentifierRequest;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonIdentifier;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\ActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersonIdentifierController extends Controller
{
    public function create(Request $request, Person $person, ActiveOrganization $activeOrganization): View
    {
        $organizationId = $activeOrganization->ensurePerson($request, $person, true);

        $organizations = Organization::query()
            ->whereKey($organizationId)
            ->where('status', 'active')
            ->get();

        return view('people.identifiers.create', compact('person', 'organizations'));
    }

    public function store(
        StorePersonIdentifierRequest $request,
        Person $person,
        AuditLogger $audit,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $data = $request->validated();
        $organizationId = $activeOrganization->ensurePerson($request, $person, true);
        $activeOrganization->ensure($request, (int) $data['organization_id']);
        $this->ensurePersonBelongsToOrganization($person, $organizationId);

        $fingerprint = PersonIdentifier::fingerprintFor($data['type'], $data['value']);
        $duplicates = PersonIdentifier::query()
            ->where('organization_id', $organizationId)
            ->where('type', $data['type'])
            ->where('value_fingerprint', $fingerprint)
            ->where('person_id', '!=', $person->id)
            ->with('person:id,uuid,display_name,social_name')
            ->get();

        if ($duplicates->isNotEmpty() && ! ($data['confirm_duplicate'] ?? false)) {
            throw ValidationException::withMessages([
                'value' => 'Possível duplicidade encontrada. Revise os registros indicados e confirme conscientemente para continuar.',
            ])->redirectTo(route('people.identifiers.create', $person).'?duplicate=1');
        }

        if ($data['is_primary'] ?? false) {
            $person->identifiers()
                ->where('organization_id', $organizationId)
                ->where('type', $data['type'])
                ->update(['is_primary' => false]);
        }

        $identifier = $person->identifiers()->create([
            'organization_id' => $organizationId,
            'type' => $data['type'],
            'value' => $data['value'],
            'issuer' => $data['issuer'] ?? null,
            'country' => isset($data['country']) ? strtoupper($data['country']) : null,
            'state' => isset($data['state']) ? strtoupper($data['state']) : null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_primary' => (bool) ($data['is_primary'] ?? false),
            'notes' => $data['notes'] ?? null,
        ]);

        $audit->record('person_identifier.created', $identifier, $organizationId, [
            'person_id' => $person->id,
            'type' => $identifier->type,
            'masked_value' => $identifier->masked_value,
            'confirmed_duplicate' => (bool) ($data['confirm_duplicate'] ?? false),
        ], $request);

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Identificador protegido adicionado com sucesso.');
    }

    private function ensurePersonBelongsToOrganization(Person $person, int $organizationId): void
    {
        $belongs = $person->memberships()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->exists();

        if (! $belongs) {
            throw ValidationException::withMessages([
                'organization_id' => 'A pessoa não possui vínculo ativo com a organização selecionada.',
            ]);
        }
    }
}
