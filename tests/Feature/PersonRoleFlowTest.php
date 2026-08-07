<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\PersonRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonRoleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_form_is_available_for_linked_person(): void
    {
        [$organization, $person] = $this->context();

        $this->get(route('people.roles.create', $person))
            ->assertOk()
            ->assertSee($organization->name);
    }

    public function test_person_can_receive_an_additional_contextual_role(): void
    {
        [$organization, $person] = $this->context();

        $this->post(route('people.roles.store', $person), [
            'organization_id' => $organization->id,
            'role' => 'instructor',
            'abilities' => ['scenarios.view', 'scenarios.manage', 'scenarios.view'],
        ])->assertRedirect(route('people.show', $person));

        $role = PersonRole::firstOrFail();

        $this->assertSame('instructor', $role->role);
        $this->assertSame(['scenarios.view', 'scenarios.manage'], $role->abilities);
        $this->assertNull($role->revoked_at);
    }

    public function test_role_requires_active_membership_with_selected_organization(): void
    {
        [$organization, $person] = $this->context();
        $foreign = Organization::create(['name' => 'Organização sem vínculo']);

        $this->post(route('people.roles.store', $person), [
            'organization_id' => $foreign->id,
            'role' => 'viewer',
        ])->assertSessionHasErrors('organization_id');

        $this->assertDatabaseCount('person_roles', 0);
    }

    public function test_equivalent_active_role_is_rejected(): void
    {
        [$organization, $person] = $this->context();

        PersonRole::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'role' => 'evaluator',
            'granted_at' => now(),
        ]);

        $this->post(route('people.roles.store', $person), [
            'organization_id' => $organization->id,
            'role' => 'evaluator',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseCount('person_roles', 1);
    }

    public function test_role_can_be_revoked_without_deleting_history(): void
    {
        [$organization, $person] = $this->context();

        $role = PersonRole::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'role' => 'support',
            'granted_at' => now(),
        ]);

        $this->patch(route('people.roles.revoke', [$person, $role]))
            ->assertRedirect(route('people.show', $person));

        $this->assertNotNull($role->fresh()->revoked_at);
        $this->assertDatabaseHas('person_roles', ['id' => $role->id]);
    }

    public function test_role_from_another_person_cannot_be_revoked(): void
    {
        [$organization, $person] = $this->context();
        $other = Person::create(['display_name' => 'Outra Pessoa']);

        $role = PersonRole::create([
            'person_id' => $other->id,
            'organization_id' => $organization->id,
            'role' => 'viewer',
            'granted_at' => now(),
        ]);

        $this->patch(route('people.roles.revoke', [$person, $role]))
            ->assertNotFound();

        $this->assertNull($role->fresh()->revoked_at);
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
