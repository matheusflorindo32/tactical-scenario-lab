<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_access_administration(): void
    {
        $this->get(route('access.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_access_manage_is_forbidden(): void
    {
        $organization = Organization::create([
            'name' => 'Organização Operacional',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'viewer',
            'abilities' => [AccessAbility::PEOPLE_VIEW],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('access.index'))
            ->assertForbidden();
    }

    public function test_access_manager_only_sees_active_grants_from_current_organization(): void
    {
        $organizationA = Organization::create([
            'name' => 'Organização Alfa',
            'status' => 'active',
        ]);
        $organizationB = Organization::create([
            'name' => 'Organização Bravo',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'name' => 'Gestor Alfa',
            'email' => 'gestor.alfa@example.com',
            'status' => 'active',
        ]);
        $operatorA = User::factory()->create([
            'name' => 'Operador Alfa',
            'email' => 'operador.alfa@example.com',
            'status' => 'active',
        ]);
        $revokedA = User::factory()->create([
            'name' => 'Revogado Alfa',
            'email' => 'revogado.alfa@example.com',
            'status' => 'active',
        ]);
        $operatorB = User::factory()->create([
            'name' => 'Operador Bravo',
            'email' => 'operador.bravo@example.com',
            'status' => 'active',
        ]);

        UserOrganizationAccess::create([
            'user_id' => $admin->id,
            'organization_id' => $organizationA->id,
            'role' => 'access_manager',
            'abilities' => [AccessAbility::ACCESS_MANAGE],
            'granted_at' => now(),
        ]);
        UserOrganizationAccess::create([
            'user_id' => $operatorA->id,
            'organization_id' => $organizationA->id,
            'role' => 'operator',
            'abilities' => [AccessAbility::PEOPLE_VIEW],
            'granted_at' => now(),
        ]);
        UserOrganizationAccess::create([
            'user_id' => $revokedA->id,
            'organization_id' => $organizationA->id,
            'role' => 'operator',
            'abilities' => [AccessAbility::PEOPLE_VIEW],
            'granted_at' => now()->subDay(),
            'revoked_at' => now(),
        ]);
        UserOrganizationAccess::create([
            'user_id' => $operatorB->id,
            'organization_id' => $organizationB->id,
            'role' => 'operator',
            'abilities' => [AccessAbility::PEOPLE_VIEW],
            'granted_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_organization_id' => $organizationA->id])
            ->get(route('access.index'));

        $response
            ->assertOk()
            ->assertSee('Organização Alfa')
            ->assertSee('Gestor Alfa')
            ->assertSee('Operador Alfa')
            ->assertDontSee('Revogado Alfa')
            ->assertDontSee('Operador Bravo');
    }
}
