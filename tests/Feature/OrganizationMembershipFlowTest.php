<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationMembershipFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_form_is_available(): void
    {
        $organization = Organization::create(['name' => 'Organização Alfa']);
        $person = Person::create(['display_name' => 'Pessoa Multiinstitucional']);
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $this->get(route('people.memberships.create', $person))
            ->assertOk()
            ->assertSee('Adicionar vínculo')
            ->assertSee($person->display_name);
    }

    public function test_person_can_receive_an_additional_membership(): void
    {
        $first = Organization::create(['name' => 'Organização Alfa']);
        $second = Organization::create(['name' => 'Organização Bravo']);
        $person = Person::create(['display_name' => 'Pessoa Multiinstitucional']);

        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $first->id,
            'status' => 'active',
        ]);

        $this->post(route('people.memberships.store', $person), [
            'organization_id' => $second->id,
            'position' => 'Instrutor convidado',
            'status' => 'active',
        ])->assertRedirect(route('people.show', $person));

        $this->assertDatabaseHas('organization_memberships', [
            'person_id' => $person->id,
            'organization_id' => $second->id,
            'position' => 'Instrutor convidado',
            'status' => 'active',
        ]);
        $this->assertSame(2, $person->memberships()->count());
    }

    public function test_membership_unit_must_belong_to_selected_organization(): void
    {
        $first = Organization::create(['name' => 'Organização Alfa']);
        $second = Organization::create(['name' => 'Organização Bravo']);
        $person = Person::create(['display_name' => 'Pessoa Protegida']);
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $first->id,
            'status' => 'active',
        ]);
        $foreignUnit = Unit::create([
            'organization_id' => $second->id,
            'name' => 'Unidade externa',
        ]);

        $this->post(route('people.memberships.store', $person), [
            'organization_id' => $first->id,
            'unit_id' => $foreignUnit->id,
            'status' => 'active',
        ])->assertSessionHasErrors('unit_id');

        $this->assertSame(1, $person->memberships()->count());
    }

    public function test_equivalent_active_membership_is_rejected(): void
    {
        $organization = Organization::create(['name' => 'Organização Alfa']);
        $person = Person::create(['display_name' => 'Pessoa sem duplicidade']);

        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $this->post(route('people.memberships.store', $person), [
            'organization_id' => $organization->id,
            'status' => 'active',
        ])->assertSessionHasErrors('organization_id');

        $this->assertSame(1, $person->memberships()->count());
    }

    public function test_end_date_cannot_precede_start_date(): void
    {
        $organization = Organization::create(['name' => 'Organização Alfa']);
        $person = Person::create(['display_name' => 'Pessoa Histórica']);

        $this->post(route('people.memberships.store', $person), [
            'organization_id' => $organization->id,
            'started_at' => '2026-08-10',
            'ended_at' => '2026-08-01',
            'status' => 'inactive',
        ])->assertSessionHasErrors('ended_at');
    }
}
