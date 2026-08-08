<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleAbilityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_only_access_can_read_people_but_cannot_manage_them(): void
    {
        [$organization, $person] = $this->context(['people.view']);

        $this->get(route('people.index'))->assertOk();
        $this->get(route('people.show', $person))->assertOk();

        $this->get(route('people.create'))->assertForbidden();
        $this->get(route('people.edit', $person))->assertForbidden();
        $this->put(route('people.update', $person), [
            'display_name' => 'Tentativa sem permissão',
            'status' => 'active',
        ])->assertForbidden();
        $this->patch(route('people.deactivate', $person))->assertForbidden();

        $this->assertSame('Pessoa Protegida', $person->fresh()->display_name);
        $this->assertSame('active', $person->fresh()->status);
        $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
    }

    public function test_manage_access_allows_person_creation_and_changes(): void
    {
        [$organization, $person] = $this->context(['people.view', 'people.manage']);

        $this->get(route('people.create'))->assertOk();
        $this->get(route('people.edit', $person))->assertOk();

        $this->put(route('people.update', $person), [
            'display_name' => 'Pessoa Atualizada',
            'status' => 'active',
        ])->assertRedirect(route('people.show', $person));

        $this->assertSame('Pessoa Atualizada', $person->fresh()->display_name);
        $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
    }

    private function context(array $abilities): array
    {
        $organization = Organization::create([
            'name' => 'Organização Diamante',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $person = Person::create([
            'display_name' => 'Pessoa Protegida',
            'status' => 'active',
        ]);
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'viewer',
            'abilities' => $abilities,
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);

        return [$organization, $person];
    }
}
