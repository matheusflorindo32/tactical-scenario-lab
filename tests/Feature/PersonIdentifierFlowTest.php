<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\PersonIdentifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonIdentifierFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_identifier_form_is_available_for_linked_person(): void
    {
        [$organization, $person] = $this->context();

        $this->get(route('people.identifiers.create', $person))
            ->assertOk()
            ->assertSee('Adicionar identificador')
            ->assertSee($organization->name);
    }

    public function test_identifier_is_stored_encrypted_and_masked(): void
    {
        [$organization, $person] = $this->context();

        $response = $this->post(route('people.identifiers.store', $person), [
            'organization_id' => $organization->id,
            'type' => 'cpf',
            'value' => '123.456.789-09',
            'is_primary' => 1,
        ]);

        $identifier = PersonIdentifier::firstOrFail();

        $response->assertRedirect(route('people.show', $person));
        $this->assertSame('***.***.***-09', $identifier->masked_value);
        $this->assertSame('123.456.789-09', $identifier->value);
        $this->assertNotSame('123.456.789-09', $identifier->getRawOriginal('value_encrypted'));
        $this->assertTrue($identifier->is_primary);
    }

    public function test_identifier_requires_person_membership_in_selected_organization(): void
    {
        [$organization, $person] = $this->context();
        $foreign = Organization::create(['name' => 'Organização sem vínculo']);

        $this->post(route('people.identifiers.store', $person), [
            'organization_id' => $foreign->id,
            'type' => 'matricula',
            'value' => 'ABC-123',
        ])->assertSessionHasErrors('organization_id');

        $this->assertDatabaseCount('person_identifiers', 0);
        $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
    }

    public function test_possible_duplicate_requires_explicit_confirmation(): void
    {
        $organization = Organization::create(['name' => 'Tactical Medicine Academy']);
        $first = Person::create(['display_name' => 'Primeira Pessoa']);
        $second = Person::create(['display_name' => 'Segunda Pessoa']);

        foreach ([$first, $second] as $person) {
            OrganizationMembership::create([
                'person_id' => $person->id,
                'organization_id' => $organization->id,
                'status' => 'active',
            ]);
        }

        PersonIdentifier::create([
            'person_id' => $first->id,
            'organization_id' => $organization->id,
            'type' => 'matricula',
            'value' => 'ABC-123',
        ]);

        $this->post(route('people.identifiers.store', $second), [
            'organization_id' => $organization->id,
            'type' => 'matricula',
            'value' => 'abc-123',
        ])->assertSessionHasErrors('value');

        $this->assertDatabaseCount('person_identifiers', 1);

        $this->post(route('people.identifiers.store', $second), [
            'organization_id' => $organization->id,
            'type' => 'matricula',
            'value' => 'abc-123',
            'confirm_duplicate' => 1,
        ])->assertRedirect(route('people.show', $second));

        $this->assertDatabaseCount('person_identifiers', 2);
    }

    /**
     * @return array{Organization, Person}
     */
    private function context(): array
    {
        $organization = Organization::create(['name' => 'Tactical Medicine Academy']);
        $person = Person::create(['display_name' => 'Pessoa de Teste']);

        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        return [$organization, $person];
    }
}
