<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationLifecycleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_organization_is_not_considered_an_active_access_context(): void
    {
        $organization = Organization::create([
            'name' => 'Organização Inativa',
            'status' => 'inactive',
        ]);
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'access_manager',
            'abilities' => [AccessAbility::ACCESS_MANAGE],
            'granted_at' => now(),
        ]);

        $this->assertFalse($user->fresh()->hasOrganizationAccess($organization->id));
        $this->assertSame(0, $user->fresh()->activeOrganizationAccesses()->count());

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_deactivating_organization_invalidates_existing_context_without_revoking_history(): void
    {
        $organization = Organization::create([
            'name' => 'Organização Temporária',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);
        $access = UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'viewer',
            'abilities' => [AccessAbility::SCENARIOS_VIEW],
            'granted_at' => now(),
        ]);

        $this->assertTrue($user->fresh()->hasOrganizationAccess($organization->id));

        $organization->update(['status' => 'inactive']);

        $this->assertFalse($user->fresh()->hasOrganizationAccess($organization->id));
        $this->assertNull($access->fresh()->revoked_at);
    }
}
