<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonContactRequest;
use App\Models\Person;
use App\Models\PersonContact;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\ActiveOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersonContactController extends Controller
{
    public function create(Request $request, Person $person, ActiveOrganization $activeOrganization): View
    {
        $organizationId = $activeOrganization->ensurePerson($request, $person, true);

        $person->load([
            'memberships' => fn ($query) => $query
                ->where('organization_id', $organizationId)
                ->where('status', 'active')
                ->whereNull('ended_at'),
            'memberships.organization',
        ]);

        return view('people.contacts.create', compact('person'));
    }

    public function store(
        StorePersonContactRequest $request,
        Person $person,
        AuditLogger $audit,
        ActiveOrganization $activeOrganization,
    ): RedirectResponse {
        $data = $request->validated();
        $organizationId = $activeOrganization->ensurePerson($request, $person, true);
        $activeOrganization->ensure($request, (int) $data['organization_id']);
        $this->ensureMembership($person, $organizationId);

        $fingerprint = PersonContact::fingerprintFor($data['type'], $data['value']);
        $duplicateExists = PersonContact::query()
            ->where('organization_id', $organizationId)
            ->where('type', $data['type'])
            ->where('value_fingerprint', $fingerprint)
            ->exists();

        if ($duplicateExists && ! ($data['confirmed_duplicate'] ?? false)) {
            return back()
                ->withInput()
                ->with('duplicate_warning', 'Já existe um contato equivalente nesta organização. Confirme conscientemente para prosseguir sem mesclagem automática.');
        }

        $contact = DB::transaction(function () use ($data, $person, $organizationId): PersonContact {
            if ($data['is_primary'] ?? false) {
                PersonContact::query()
                    ->where('person_id', $person->id)
                    ->where('organization_id', $organizationId)
                    ->where('type', $data['type'])
                    ->update(['is_primary' => false]);
            }

            return PersonContact::create([
                'person_id' => $person->id,
                'organization_id' => $organizationId,
                'type' => $data['type'],
                'value' => $data['value'],
                'label' => $data['label'] ?? null,
                'is_primary' => (bool) ($data['is_primary'] ?? false),
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $audit->record('person_contact.created', $contact, $organizationId, [
            'person_id' => $person->id,
            'type' => $contact->type,
            'masked_value' => $contact->masked_value,
            'confirmed_duplicate' => (bool) ($data['confirmed_duplicate'] ?? false),
        ], $request);

        return redirect()
            ->route('people.show', $person)
            ->with('success', 'Contato protegido cadastrado com sucesso.');
    }

    private function ensureMembership(Person $person, int $organizationId): void
    {
        if (! $person->memberships()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereNull('ended_at')
            ->exists()) {
            throw ValidationException::withMessages([
                'organization_id' => 'A pessoa não possui vínculo ativo com a organização selecionada.',
            ]);
        }
    }
}
