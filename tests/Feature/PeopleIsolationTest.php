<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\PersonIdentifier;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_people_listing_and_profile_are_scoped_to_active_organization(): void
    {
        [$user, $allowed, $outside] = $this->institutionalContext();

        $visible = Person::create(['display_name' => 'Pessoa Visível']);
        $hidden = Person::create(['display_name' => 'Pessoa Oculta']);

        OrganizationMembership::create([
            'person_id' => $visible->id,
            'organization_id' => $allowed->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);
        OrganizationMembership::create([
            'person_id' => $hidden->id,
            'organization_id' => $outside->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $this->actingAs($user)->withSession(['active_organization_id' => $allowed->id]);

        $this->get(route('people.index'))
            ->assertOk()
            ->assertSee('Pessoa Visível')
            ->assertDontSee('Pessoa Oculta');

        $this->get(route('people.show', $visible))->assertOk();
        $this->get(route('people.show', $hidden))->assertForbidden();
        $this->get(route('people.edit', $hidden))->assertForbidden();
        $this->patch(route('people.deactivate', $hidden))->assertForbidden();

        $this->assertDatabaseHas('people', [
            'id' => $hidden->id,
            'status' => 'incomplete',
        ]);
    }

    public function test_profile_hides_relationships_from_other_organizations(): void
    {
        [$user, $allowed, $outside] = $this->institutionalContext();
        $person = Person::create(['display_name' => 'Pessoa Multiinstitucional']);

        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $allowed->id,
            'position' => 'Posição Permitida',
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $outside->id,
            'position' => 'Posição Sigilosa Externa',
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        PersonIdentifier::create([
            'person_id' => $person->id,
            'organization_id' => $allowed->id,
            'type' => 'temp_code',
            'value' => 'TMA-PERMITIDO',
            'is_primary' => true,
        ]);
        PersonIdentifier::create([
            'person_id' => $person->id,
            'organization_id' => $outside->id,
            'type' => 'temp_code',
            'value' => 'TMA-EXTERNO',
            'is_primary' => true,
        ]);

        $this->actingAs($user)->withSession(['active_organization_id' => $allowed->id]);

        $this->get(route('people.show', $person))
            ->assertOk()
            ->assertSee('Posição Permitida')
            ->assertDontSee('Posição Sigilosa Externa')
            ->assertDontSee($outside->name);
    }

    public function test_person_cannot_be_created_for_another_organization_by_crafted_request(): void
    {
        [$user, $allowed, $outside] = $this->institutionalContext();

        $this->actingAs($user)->withSession(['active_organization_id' => $allowed->id]);

        $this->post(route('people.store'), [
            'display_name' => 'Cadastro Indevido',
            'organization_id' => $outside->id,
            'role' => 'student',
        ])->assertForbidden();

        $this->assertDatabaseMissing('people', ['display_name' => 'Cadastro Indevido']);
    }

    private function institutionalContext(): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $allowed = Organization::create([
            'name' => 'Organização Permitida',
            'kind' => 'military',
            'status' => 'active',
        ]);
        $outside = Organization::create([
            'name' => 'Organização Externa',
            'kind' => 'military',
            'status' => 'active',
        ]);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $allowed->id,
            'role' => 'manager_org',
            'abilities' => ['people.view', 'people.manage'],
            'granted_at' => now(),
        ]);

        return [$user, $allowed, $outside];
    }
}
