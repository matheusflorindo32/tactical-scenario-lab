<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\PersonContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PersonContactFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_is_available_for_linked_person(): void
    {
        [$organization, $person] = $this->context();

        $this->get(route('people.contacts.create', $person))
            ->assertOk()
            ->assertSee('Adicionar contato')
            ->assertSee($organization->name);
    }

    public function test_phone_is_encrypted_masked_and_saved(): void
    {
        [$organization, $person] = $this->context();

        $response = $this->post(route('people.contacts.store', $person), [
            'organization_id' => $organization->id,
            'type' => 'phone',
            'value' => '(27) 99999-8080',
            'label' => 'Pessoal',
            'is_primary' => 1,
        ]);

        $contact = PersonContact::firstOrFail();
        $stored = DB::table('person_contacts')->where('id', $contact->id)->first();

        $response->assertRedirect(route('people.show', $person));
        $this->assertNotSame('(27) 99999-8080', $stored->value_encrypted);
        $this->assertSame('(27) *****-80', $stored->masked_value);
        $this->assertSame('(27) 99999-8080', $contact->fresh()->value);
        $this->assertTrue($contact->is_primary);
    }

    public function test_contact_requires_membership_with_selected_organization(): void
    {
        [, $person] = $this->context();
        $foreign = Organization::create(['name' => 'Organização externa']);

        $this->post(route('people.contacts.store', $person), [
            'organization_id' => $foreign->id,
            'type' => 'email',
            'value' => 'teste@example.com',
        ])->assertForbidden();

        $this->assertDatabaseCount('person_contacts', 0);
    }

    public function test_possible_duplicate_requires_explicit_confirmation(): void
    {
        [$organization, $first] = $this->context();
        $second = Person::create(['display_name' => 'Segunda pessoa']);
        OrganizationMembership::create([
            'person_id' => $second->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        PersonContact::create([
            'person_id' => $first->id,
            'organization_id' => $organization->id,
            'type' => 'email',
            'value' => 'CONTATO@EXAMPLE.COM',
        ]);

        $this->post(route('people.contacts.store', $second), [
            'organization_id' => $organization->id,
            'type' => 'email',
            'value' => 'contato@example.com',
        ])->assertSessionHas('duplicate_warning');

        $this->assertDatabaseCount('person_contacts', 1);

        $this->post(route('people.contacts.store', $second), [
            'organization_id' => $organization->id,
            'type' => 'email',
            'value' => 'contato@example.com',
            'confirmed_duplicate' => 1,
        ])->assertRedirect(route('people.show', $second));

        $this->assertDatabaseCount('person_contacts', 2);
    }

    public function test_new_primary_contact_demotes_previous_primary_of_same_type(): void
    {
        [$organization, $person] = $this->context();

        $first = PersonContact::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'type' => 'phone',
            'value' => '27999990000',
            'is_primary' => true,
        ]);

        $this->post(route('people.contacts.store', $person), [
            'organization_id' => $organization->id,
            'type' => 'phone',
            'value' => '27988880000',
            'is_primary' => 1,
        ])->assertRedirect(route('people.show', $person));

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue(PersonContact::query()->latest('id')->firstOrFail()->is_primary);
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
