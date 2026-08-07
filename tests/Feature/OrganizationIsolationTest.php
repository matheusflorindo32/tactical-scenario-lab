<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_lists_organizations_with_active_access(): void
    {
        [$user, $allowed, $blocked] = $this->institutionalContext();

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $allowed->id])
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertSee($allowed->name)
            ->assertDontSee($blocked->name);
    }

    public function test_cross_organization_reads_and_writes_are_forbidden(): void
    {
        [$user, $allowed, $blocked] = $this->institutionalContext();
        $allowedUnit = Unit::create([
            'organization_id' => $allowed->id,
            'name' => 'Unidade Permitida',
            'kind' => 'department',
            'status' => 'active',
        ]);
        $blockedUnit = Unit::create([
            'organization_id' => $blocked->id,
            'name' => 'Unidade Bloqueada',
            'kind' => 'department',
            'status' => 'active',
        ]);

        $client = $this->actingAs($user)
            ->withSession(['active_organization_id' => $allowed->id]);

        $client->get(route('organizations.show', $allowed))->assertOk();
        $client->get(route('organizations.show', $blocked))->assertForbidden();
        $client->get(route('organizations.edit', $blocked))->assertForbidden();
        $client->patch(route('organizations.deactivate', $blocked))->assertForbidden();

        $client->get(route('units.edit', $allowedUnit))->assertOk();
        $client->get(route('organizations.units.create', $blocked))->assertForbidden();
        $client->get(route('units.edit', $blockedUnit))->assertForbidden();
        $client->put(route('units.update', $blockedUnit), [
            'name' => 'Tentativa cruzada',
            'kind' => 'department',
            'status' => 'active',
        ])->assertForbidden();
        $client->patch(route('units.deactivate', $blockedUnit))->assertForbidden();
        $client->post(route('units.store'), [
            'organization_id' => $blocked->id,
            'name' => 'Unidade indevida',
            'kind' => 'department',
            'status' => 'active',
        ])->assertForbidden();

        $this->assertDatabaseMissing('units', ['name' => 'Unidade indevida']);
        $this->assertDatabaseHas('units', [
            'id' => $blockedUnit->id,
            'name' => 'Unidade Bloqueada',
            'status' => 'active',
        ]);
    }

    private function institutionalContext(): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $allowed = Organization::create([
            'name' => 'Organização Permitida',
            'kind' => 'military',
            'status' => 'active',
        ]);
        $blocked = Organization::create([
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

        return [$user, $allowed, $blocked];
    }
}
