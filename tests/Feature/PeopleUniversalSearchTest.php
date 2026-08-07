<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\PersonContact;
use App\Models\PersonIdentifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleUniversalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_and_social_name_support_partial_search(): void
    {
        [$organization, $person] = $this->personContext('Matheus Florindo', 'Theo');
        $other = Person::create(['display_name' => 'Outra Pessoa']);

        $this->get(route('people.index', ['q' => 'Flor']))
            ->assertOk()
            ->assertSee($person->display_name)
            ->assertDontSee($other->display_name);

        $this->get(route('people.index', ['q' => 'Theo']))
            ->assertOk()
            ->assertSee($person->social_name);
    }

    public function test_cpf_search_accepts_formatted_and_unformatted_exact_values(): void
    {
        [$organization, $person] = $this->personContext('Pessoa CPF');

        PersonIdentifier::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'type' => 'cpf',
            'value' => '123.456.789-09',
        ]);

        foreach (['123.456.789-09', '12345678909'] as $query) {
            $this->get(route('people.index', ['q' => $query]))
                ->assertOk()
                ->assertSee($person->display_name)
                ->assertDontSee('123.456.789-09');
        }
    }

    public function test_email_and_phone_are_found_by_exact_protected_fingerprint(): void
    {
        [$organization, $person] = $this->personContext('Pessoa Contato');

        PersonContact::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'type' => 'email',
            'value' => 'Contato.Premium@example.com',
        ]);

        PersonContact::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'type' => 'whatsapp',
            'value' => '(27) 99999-8080',
        ]);

        $this->get(route('people.index', ['q' => 'contato.premium@EXAMPLE.COM']))
            ->assertOk()
            ->assertSee($person->display_name)
            ->assertDontSee('Contato.Premium@example.com');

        $this->get(route('people.index', ['q' => '27999998080']))
            ->assertOk()
            ->assertSee($person->display_name)
            ->assertDontSee('(27) 99999-8080');
    }

    public function test_partial_document_or_contact_value_does_not_match(): void
    {
        [$organization, $person] = $this->personContext('Pessoa Protegida');

        PersonIdentifier::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'type' => 'matricula',
            'value' => 'MATRICULA-ELITE-2026',
        ]);

        PersonContact::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'type' => 'email',
            'value' => 'elite@example.com',
        ]);

        $this->get(route('people.index', ['q' => 'ELITE-2026']))
            ->assertOk()
            ->assertDontSee($person->display_name);

        $this->get(route('people.index', ['q' => 'elite@']))
            ->assertOk()
            ->assertDontSee($person->display_name);
    }

    public function test_organization_filter_scopes_identifier_matches(): void
    {
        $firstOrganization = Organization::create(['name' => 'Organização Alfa']);
        $secondOrganization = Organization::create(['name' => 'Organização Bravo']);
        $firstPerson = Person::create(['display_name' => 'Pessoa Alfa']);
        $secondPerson = Person::create(['display_name' => 'Pessoa Bravo']);

        foreach ([[$firstOrganization, $firstPerson], [$secondOrganization, $secondPerson]] as [$organization, $person]) {
            OrganizationMembership::create([
                'person_id' => $person->id,
                'organization_id' => $organization->id,
                'status' => 'active',
            ]);

            PersonIdentifier::create([
                'person_id' => $person->id,
                'organization_id' => $organization->id,
                'type' => 'matricula',
                'value' => 'CODIGO-COMPARTILHADO',
            ]);
        }

        $this->get(route('people.index', [
            'q' => 'CODIGO-COMPARTILHADO',
            'organization_id' => $firstOrganization->id,
        ]))
            ->assertOk()
            ->assertSee($firstPerson->display_name)
            ->assertDontSee($secondPerson->display_name);
    }

    /**
     * @return array{Organization, Person}
     */
    private function personContext(string $displayName, ?string $socialName = null): array
    {
        $organization = Organization::create(['name' => 'Tactical Medicine Academy']);
        $person = Person::create([
            'display_name' => $displayName,
            'social_name' => $socialName,
        ]);

        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        return [$organization, $person];
    }
}
