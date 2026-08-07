<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\PersonRole;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_people_and_sensitive_workflows(): void
    {
        [$person, $membership, $role] = $this->personContext();

        $this->get(route('people.index'))->assertRedirect(route('login'));
        $this->get(route('people.create'))->assertRedirect(route('login'));
        $this->get(route('people.show', $person))->assertRedirect(route('login'));
        $this->get(route('people.edit', $person))->assertRedirect(route('login'));
        $this->post(route('people.store'), [])->assertRedirect(route('login'));
        $this->put(route('people.update', $person), [])->assertRedirect(route('login'));
        $this->patch(route('people.deactivate', $person))->assertRedirect(route('login'));

        $this->get(route('people.identifiers.create', $person))->assertRedirect(route('login'));
        $this->post(route('people.identifiers.store', $person), [])->assertRedirect(route('login'));
        $this->get(route('people.contacts.create', $person))->assertRedirect(route('login'));
        $this->post(route('people.contacts.store', $person), [])->assertRedirect(route('login'));

        $this->get(route('people.memberships.create', $person))->assertRedirect(route('login'));
        $this->post(route('people.memberships.store', $person), [])->assertRedirect(route('login'));
        $this->patch(route('people.memberships.close', [$person, $membership]))->assertRedirect(route('login'));

        $this->get(route('people.roles.create', $person))->assertRedirect(route('login'));
        $this->post(route('people.roles.store', $person), [])->assertRedirect(route('login'));
        $this->patch(route('people.roles.revoke', [$person, $role]))->assertRedirect(route('login'));
    }

    public function test_authenticated_active_user_can_open_people_workflow(): void
    {
        [$person, , , $organization] = $this->personContext();
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'viewer',
            'abilities' => ['people.view'],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);

        $this->get(route('people.index'))->assertOk();
        $this->get(route('people.create'))->assertOk();
        $this->get(route('people.show', $person))->assertOk();
    }

    private function personContext(): array
    {
        $organization = Organization::create([
            'name' => 'Organização Protegida',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $person = Person::create(['display_name' => 'Pessoa Protegida']);
        $membership = OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);
        $role = PersonRole::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'role' => 'viewer',
            'abilities' => ['people.view'],
            'granted_at' => now(),
        ]);

        return [$person, $membership, $role, $organization];
    }
}
