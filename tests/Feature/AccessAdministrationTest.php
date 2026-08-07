<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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

        $this->grant($admin, $organizationA, 'access_manager', [AccessAbility::ACCESS_MANAGE]);
        $this->grant($operatorA, $organizationA, 'operator', [AccessAbility::PEOPLE_VIEW]);
        UserOrganizationAccess::create([
            'user_id' => $revokedA->id,
            'organization_id' => $organizationA->id,
            'role' => 'operator',
            'abilities' => [AccessAbility::PEOPLE_VIEW],
            'granted_at' => now()->subDay(),
            'revoked_at' => now(),
        ]);
        $this->grant($operatorB, $organizationB, 'operator', [AccessAbility::PEOPLE_VIEW]);

        $response = $this->asAdmin($admin, $organizationA)
            ->get(route('access.index'));

        $response
            ->assertOk()
            ->assertSee('Organização Alfa')
            ->assertSee('Gestor Alfa')
            ->assertSee('Operador Alfa')
            ->assertDontSee('Revogado Alfa')
            ->assertDontSee('Operador Bravo');
    }

    public function test_access_manager_can_grant_and_regrant_access_without_duplicate_history_row(): void
    {
        $organization = Organization::create(['name' => 'Organização Diamante', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create([
            'email' => 'novo.operador@example.com',
            'status' => 'active',
        ]);
        $this->grant($admin, $organization, 'access_manager', [AccessAbility::ACCESS_MANAGE]);

        $this->asAdmin($admin, $organization)
            ->post(route('access.store'), [
                'email' => 'NOVO.OPERADOR@EXAMPLE.COM',
                'role' => 'operator',
                'abilities' => [AccessAbility::PEOPLE_VIEW, AccessAbility::SCENARIOS_VIEW],
            ])
            ->assertRedirect(route('access.index'));

        $access = UserOrganizationAccess::query()
            ->where('user_id', $target->id)
            ->where('organization_id', $organization->id)
            ->where('role', 'operator')
            ->firstOrFail();

        $this->assertNull($access->revoked_at);
        $this->assertSame([AccessAbility::PEOPLE_VIEW, AccessAbility::SCENARIOS_VIEW], $access->abilities);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'access.granted',
            'organization_id' => $organization->id,
            'subject_id' => $access->id,
        ]);

        $access->update(['revoked_at' => now()]);

        $this->asAdmin($admin, $organization)
            ->post(route('access.store'), [
                'email' => 'novo.operador@example.com',
                'role' => 'operator',
                'abilities' => [AccessAbility::SCENARIOS_VIEW],
            ])
            ->assertRedirect(route('access.index'));

        $this->assertSame(1, UserOrganizationAccess::query()
            ->where('user_id', $target->id)
            ->where('organization_id', $organization->id)
            ->where('role', 'operator')
            ->count());
        $this->assertNull($access->fresh()->revoked_at);
        $this->assertSame([AccessAbility::SCENARIOS_VIEW], $access->fresh()->abilities);
    }

    public function test_access_manager_can_update_abilities_and_revoke_access_with_audit(): void
    {
        $organization = Organization::create(['name' => 'Organização Controle', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create(['status' => 'active']);
        $this->grant($admin, $organization, 'access_manager', [AccessAbility::ACCESS_MANAGE]);
        $access = $this->grant($target, $organization, 'operator', [AccessAbility::PEOPLE_VIEW]);

        $this->asAdmin($admin, $organization)
            ->put(route('access.update', $access), [
                'abilities' => [AccessAbility::PEOPLE_VIEW, AccessAbility::SCENARIOS_VIEW],
            ])
            ->assertRedirect(route('access.index'));

        $this->assertSame(
            [AccessAbility::PEOPLE_VIEW, AccessAbility::SCENARIOS_VIEW],
            $access->fresh()->abilities,
        );
        $this->assertTrue(AuditLog::query()->where('action', 'access.updated')->exists());

        $this->asAdmin($admin, $organization)
            ->patch(route('access.revoke', $access))
            ->assertRedirect(route('access.index'));

        $this->assertNotNull($access->fresh()->revoked_at);
        $this->assertTrue(AuditLog::query()->where('action', 'access.revoked')->exists());
    }

    public function test_last_access_manager_cannot_be_revoked_or_lose_access_manage(): void
    {
        $organization = Organization::create(['name' => 'Organização Protegida', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $adminAccess = $this->grant($admin, $organization, 'access_manager', [
            AccessAbility::ACCESS_MANAGE,
            AccessAbility::PEOPLE_VIEW,
        ]);

        $this->asAdmin($admin, $organization)
            ->put(route('access.update', $adminAccess), [
                'abilities' => [AccessAbility::PEOPLE_VIEW],
            ])
            ->assertSessionHasErrors('access');

        $this->assertContains(AccessAbility::ACCESS_MANAGE, $adminAccess->fresh()->abilities);

        $this->asAdmin($admin, $organization)
            ->patch(route('access.revoke', $adminAccess))
            ->assertSessionHasErrors('access');

        $this->assertNull($adminAccess->fresh()->revoked_at);
    }

    public function test_cross_organization_access_cannot_be_edited_or_revoked(): void
    {
        $organizationA = Organization::create(['name' => 'Organização A', 'status' => 'active']);
        $organizationB = Organization::create(['name' => 'Organização B', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $externalUser = User::factory()->create(['status' => 'active']);
        $this->grant($admin, $organizationA, 'access_manager', [AccessAbility::ACCESS_MANAGE]);
        $externalAccess = $this->grant($externalUser, $organizationB, 'operator', [AccessAbility::PEOPLE_VIEW]);

        $this->asAdmin($admin, $organizationA)
            ->get(route('access.edit', $externalAccess))
            ->assertForbidden();

        $this->asAdmin($admin, $organizationA)
            ->put(route('access.update', $externalAccess), [
                'abilities' => [AccessAbility::SCENARIOS_VIEW],
            ])
            ->assertForbidden();

        $this->asAdmin($admin, $organizationA)
            ->patch(route('access.revoke', $externalAccess))
            ->assertForbidden();

        $this->assertNull($externalAccess->fresh()->revoked_at);
        $this->assertSame([AccessAbility::PEOPLE_VIEW], $externalAccess->fresh()->abilities);
    }

    public function test_access_manager_can_deactivate_and_reactivate_exclusive_account_with_audit(): void
    {
        $organization = Organization::create(['name' => 'Organização Exclusiva', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create(['status' => 'active']);
        $this->grant($admin, $organization, 'access_manager', [AccessAbility::ACCESS_MANAGE]);
        $this->grant($target, $organization, 'operator', [AccessAbility::PEOPLE_VIEW]);

        $this->asAdmin($admin, $organization)
            ->patch(route('access.accounts.deactivate', $target))
            ->assertRedirect(route('access.index'));

        $this->assertSame('inactive', $target->fresh()->status);
        $this->assertTrue(AuditLog::query()->where('action', 'account.deactivated')->exists());

        $this->asAdmin($admin, $organization)
            ->patch(route('access.accounts.reactivate', $target))
            ->assertRedirect(route('access.index'));

        $this->assertSame('active', $target->fresh()->status);
        $this->assertTrue(AuditLog::query()->where('action', 'account.reactivated')->exists());
    }

    public function test_account_with_active_grant_in_another_organization_cannot_be_globally_changed(): void
    {
        $organizationA = Organization::create(['name' => 'Organização A', 'status' => 'active']);
        $organizationB = Organization::create(['name' => 'Organização B', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create(['status' => 'active']);
        $this->grant($admin, $organizationA, 'access_manager', [AccessAbility::ACCESS_MANAGE]);
        $this->grant($target, $organizationA, 'operator', [AccessAbility::PEOPLE_VIEW]);
        $this->grant($target, $organizationB, 'operator', [AccessAbility::SCENARIOS_VIEW]);

        $this->asAdmin($admin, $organizationA)
            ->patch(route('access.accounts.deactivate', $target))
            ->assertSessionHasErrors('account');

        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_admin_cannot_deactivate_self_or_leave_organization_without_active_admin_user(): void
    {
        $organization = Organization::create(['name' => 'Organização Guardada', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $backupAdmin = User::factory()->create(['status' => 'inactive']);
        $this->grant($admin, $organization, 'access_manager', [AccessAbility::ACCESS_MANAGE]);
        $this->grant($backupAdmin, $organization, 'access_manager_backup', [AccessAbility::ACCESS_MANAGE]);

        $this->asAdmin($admin, $organization)
            ->patch(route('access.accounts.deactivate', $admin))
            ->assertSessionHasErrors('account');

        $this->assertSame('active', $admin->fresh()->status);

        $operatorAdmin = User::factory()->create(['status' => 'active']);
        $operatorAccess = $this->grant($operatorAdmin, $organization, 'access_manager_secondary', [AccessAbility::ACCESS_MANAGE]);

        $this->asAdmin($admin, $organization)
            ->patch(route('access.revoke', $operatorAccess))
            ->assertRedirect(route('access.index'));

        $this->assertNotNull($operatorAccess->fresh()->revoked_at);
    }

    private function grant(User $user, Organization $organization, string $role, array $abilities): UserOrganizationAccess
    {
        return UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => $role,
            'abilities' => $abilities,
            'granted_at' => now(),
        ]);
    }

    private function asAdmin(User $user, Organization $organization): static
    {
        return $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);
    }
}
