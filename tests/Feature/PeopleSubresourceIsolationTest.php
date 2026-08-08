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

class PeopleSubresourceIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_people_subresource_forms_only_expose_active_organization(): void
    {
        [$person, $activeOrganization, $externalOrganization] = $this->context();

        $this->get(route('people.identifiers.create', $person))
            ->assertOk()
            ->assertSee($activeOrganization->name)
            ->assertDontSee($externalOrganization->name);

        $this->get(route('people.contacts.create', $person))
            ->assertOk()
            ->assertSee($activeOrganization->name)
            ->assertDontSee($externalOrganization->name);

        $this->get(route('people.memberships.create', $person))
            ->assertOk()
            ->assertSee($activeOrganization->name)
            ->assertDontSee($externalOrganization->name);

        $this->get(route('people.roles.create', $person))
            ->assertOk()
            ->assertSee($activeOrganization->name)
            ->assertDontSee($externalOrganization->name);
    }

    public function test_crafted_writes_to_external_organization_are_forbidden(): void
    {
        [$person, , $externalOrganization] = $this->context();

        $this->post(route('people.identifiers.store', $person), [
            'organization_id' => $externalOrganization->id,
            'type' => 'cpf',
            'value' => '12345678901',
        ])->assertForbidden();

        $this->post(route('people.contacts.store', $person), [
            'organization_id' => $externalOrganization->id,
            'type' => 'email',
            'value' => 'externo@example.com',
        ])->assertForbidden();

        $this->post(route('people.memberships.store', $person), [
            'organization_id' => $externalOrganization->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ])->assertForbidden();

        $this->post(route('people.roles.store', $person), [
            'organization_id' => $externalOrganization->id,
            'role' => 'viewer',
            'abilities' => ['people.view'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('person_identifiers', [
            'person_id' => $person->id,
            'organization_id' => $externalOrganization->id,
            'type' => 'cpf',
        ]);
        $this->assertDatabaseMissing('person_contacts', [
            'person_id' => $person->id,
            'organization_id' => $externalOrganization->id,
            'type' => 'email',
        ]);
    }

    public function test_external_membership_and_role_cannot_be_closed_or_revoked(): void
    {
        [$person, , $externalOrganization] = $this->context();

        $externalMembership = $person->memberships()
            ->where('organization_id', $externalOrganization->id)
            ->firstOrFail();
        $externalRole = PersonRole::create([
            'person_id' => $person->id,
            'organization_id' => $externalOrganization->id,
            'role' => 'viewer',
            'abilities' => ['people.view'],
            'granted_at' => now(),
        ]);

        $this->patch(route('people.memberships.close', [$person, $externalMembership]))
            ->assertForbidden();
        $this->patch(route('people.roles.revoke', [$person, $externalRole]))
            ->assertForbidden();

        $this->assertTrue($externalMembership->refresh()->isActive());
        $this->assertNull($externalRole->refresh()->revoked_at);
    }

    private function context(): array
    {
        $activeOrganization = Organization::create([
            'name' => 'Organização Ativa',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $externalOrganization = Organization::create([
            'name' => 'Organização Externa',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $person = Person::create(['display_name' => 'Pessoa Multi Institucional']);

        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $activeOrganization->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $externalOrganization->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $activeOrganization->id,
            'role' => 'manager_org',
            'abilities' => ['people.view', 'people.manage'],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $activeOrganization->id]);

        return [$person, $activeOrganization, $externalOrganization];
    }
}
