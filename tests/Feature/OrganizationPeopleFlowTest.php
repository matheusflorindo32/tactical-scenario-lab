<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\PersonIdentifier;
use App\Models\PersonRole;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPeopleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_pages_are_available(): void
    {
        $this->get(route('organizations.index'))->assertOk();
        $this->get(route('organizations.create'))->assertOk();
    }

    public function test_organization_can_be_created_without_optional_data(): void
    {
        $response = $this->post(route('organizations.store'), [
            'name' => 'Tactical Medicine Academy',
            'kind' => 'tma',
            'status' => 'active',
        ]);

        $organization = Organization::firstOrFail();

        $response->assertRedirect(route('organizations.show', $organization));
        $this->assertDatabaseHas('organizations', [
            'name' => 'Tactical Medicine Academy',
            'kind' => 'tma',
            'status' => 'active',
        ]);
    }

    public function test_person_can_be_registered_without_cpf_rg_email_or_phone(): void
    {
        $organization = Organization::create(['name' => 'Tactical Medicine Academy']);

        $response = $this->post(route('people.store'), [
            'display_name' => 'Aluno Alfa',
            'organization_id' => $organization->id,
            'role' => 'student',
        ]);

        $person = Person::firstOrFail();

        $response->assertRedirect(route('people.show', $person));
        $this->assertSame('incomplete', $person->status);
        $this->assertDatabaseCount('person_contacts', 0);
        $this->assertDatabaseHas('organization_memberships', [
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('person_roles', [
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'role' => 'student',
        ]);

        $temporaryCode = PersonIdentifier::firstOrFail();
        $this->assertSame('temp_code', $temporaryCode->type);
        $this->assertStringStartsWith('TMA-', $temporaryCode->value);
        $this->assertNotSame($temporaryCode->value, $temporaryCode->getRawOriginal('value_encrypted'));
    }

    public function test_selected_unit_must_belong_to_selected_organization(): void
    {
        $first = Organization::create(['name' => 'Primeira organização']);
        $second = Organization::create(['name' => 'Segunda organização']);
        $foreignUnit = Unit::create([
            'organization_id' => $second->id,
            'name' => 'Unidade externa',
        ]);

        $this->post(route('people.store'), [
            'display_name' => 'Pessoa de Teste',
            'organization_id' => $first->id,
            'unit_id' => $foreignUnit->id,
            'role' => 'student',
        ])->assertSessionHasErrors('unit_id');

        $this->assertDatabaseCount('people', 0);
        $this->assertDatabaseCount('organization_memberships', 0);
        $this->assertDatabaseCount('person_roles', 0);
    }

    public function test_person_profile_only_displays_masked_temporary_code(): void
    {
        $organization = Organization::create(['name' => 'Tactical Medicine Academy']);
        $person = Person::create(['display_name' => 'Pessoa Protegida']);

        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        PersonRole::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'role' => 'student',
        ]);

        $identifier = PersonIdentifier::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'type' => 'temp_code',
            'value' => 'TMA-SECRET1',
            'is_primary' => true,
        ])->fresh();

        $this->get(route('people.show', $person))
            ->assertOk()
            ->assertDontSee('TMA-SECRET1')
            ->assertSeeText($identifier->masked_value);
    }
}
