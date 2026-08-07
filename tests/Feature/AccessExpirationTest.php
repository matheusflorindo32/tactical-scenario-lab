<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_future_expiration_keeps_access_valid_until_deadline(): void
    {
        $organization = Organization::create(['name' => 'Organização Temporal', 'status' => 'active']);
        $user = User::factory()->create(['status' => 'active']);
        $access = $this->grant(
            $user,
            $organization,
            'viewer',
            [AccessAbility::PEOPLE_VIEW],
            now()->addHour(),
        );

        $this->assertTrue($access->isActive());
        $this->assertFalse($access->isExpired());
        $this->assertTrue($user->fresh()->hasOrganizationAccess($organization->id));

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('people.index'))
            ->assertOk();
    }

    public function test_expired_access_is_rejected_as_institutional_context(): void
    {
        $organization = Organization::create(['name' => 'Organização Expirada', 'status' => 'active']);
        $user = User::factory()->create(['status' => 'active']);
        $access = $this->grant(
            $user,
            $organization,
            'viewer',
            [AccessAbility::PEOPLE_VIEW, AccessAbility::SCENARIOS_VIEW],
            now()->subMinute(),
        );

        $this->assertTrue($access->isExpired());
        $this->assertFalse($access->isActive());
        $this->assertFalse($user->fresh()->hasOrganizationAccess($organization->id));

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('people.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('scenarios.index'))
            ->assertForbidden();
    }

    public function test_expired_grant_is_hidden_from_active_access_administration_list(): void
    {
        $organization = Organization::create(['name' => 'Organização Gestão', 'status' => 'active']);
        $admin = User::factory()->create(['name' => 'Administrador', 'status' => 'active']);
        $expiredUser = User::factory()->create(['name' => 'Acesso Expirado', 'status' => 'active']);

        $this->grant($admin, $organization, 'access_manager', [AccessAbility::ACCESS_MANAGE]);
        $this->grant(
            $expiredUser,
            $organization,
            'operator',
            [AccessAbility::PEOPLE_VIEW],
            now()->subMinute(),
        );

        $this->actingAs($admin)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('access.index'))
            ->assertOk()
            ->assertSee('Administrador')
            ->assertDontSee('Acesso Expirado');
    }

    public function test_access_forms_expose_expiration_field_and_index_shows_deadline(): void
    {
        $organization = Organization::create(['name' => 'Organização Visual', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create(['status' => 'active']);
        $deadline = now()->addDays(7)->startOfMinute();

        $this->grant($admin, $organization, 'access_manager', [AccessAbility::ACCESS_MANAGE]);
        $access = $this->grant(
            $target,
            $organization,
            'operator',
            [AccessAbility::PEOPLE_VIEW],
            $deadline,
        );

        $this->asAdmin($admin, $organization)
            ->get(route('access.create'))
            ->assertOk()
            ->assertSee('Validade do acesso')
            ->assertSee('expires_at');

        $this->asAdmin($admin, $organization)
            ->get(route('access.edit', $access))
            ->assertOk()
            ->assertSee('Validade do acesso')
            ->assertSee($deadline->format('Y-m-d\TH:i'));

        $this->asAdmin($admin, $organization)
            ->get(route('access.index'))
            ->assertOk()
            ->assertSee($deadline->format('d/m/Y H:i'));
    }

    public function test_access_manager_can_change_or_remove_expiration_with_audit(): void
    {
        $organization = Organization::create(['name' => 'Organização Editável', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create(['status' => 'active']);
        $initialDeadline = now()->addDays(5)->startOfMinute();
        $newDeadline = now()->addDays(20)->startOfMinute();

        $this->grant($admin, $organization, 'access_manager', [AccessAbility::ACCESS_MANAGE]);
        $access = $this->grant(
            $target,
            $organization,
            'operator',
            [AccessAbility::PEOPLE_VIEW],
            $initialDeadline,
        );

        $this->asAdmin($admin, $organization)
            ->put(route('access.update', $access), [
                'abilities' => [AccessAbility::PEOPLE_VIEW],
                'expires_at' => $newDeadline->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('access.index'));

        $this->assertTrue($access->fresh()->expires_at->equalTo($newDeadline));
        $this->assertTrue(AuditLog::query()->where('action', 'access.updated')->exists());

        $this->asAdmin($admin, $organization)
            ->put(route('access.update', $access), [
                'abilities' => [AccessAbility::PEOPLE_VIEW],
                'expires_at' => null,
            ])
            ->assertRedirect(route('access.index'));

        $this->assertNull($access->fresh()->expires_at);
    }

    public function test_expired_grant_can_be_regranted_with_new_validity_without_duplicate_row(): void
    {
        $organization = Organization::create(['name' => 'Organização Regrant', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create([
            'email' => 'regrant@example.com',
            'status' => 'active',
        ]);

        $this->grant($admin, $organization, 'access_manager', [AccessAbility::ACCESS_MANAGE]);
        $expired = $this->grant(
            $target,
            $organization,
            'operator',
            [AccessAbility::PEOPLE_VIEW],
            now()->subMinute(),
        );
        $newExpiration = now()->addDays(30)->startOfMinute();

        $this->actingAs($admin)
            ->withSession(['active_organization_id' => $organization->id])
            ->post(route('access.store'), [
                'email' => 'regrant@example.com',
                'role' => 'operator',
                'abilities' => [AccessAbility::SCENARIOS_VIEW],
                'expires_at' => $newExpiration->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('access.index'));

        $this->assertSame(1, UserOrganizationAccess::query()
            ->where('user_id', $target->id)
            ->where('organization_id', $organization->id)
            ->where('role', 'operator')
            ->count());

        $expired->refresh();
        $this->assertTrue($expired->isActive());
        $this->assertSame([AccessAbility::SCENARIOS_VIEW], $expired->abilities);
        $this->assertTrue($expired->expires_at->equalTo($newExpiration));
    }

    public function test_access_manage_cannot_be_granted_with_automatic_expiration(): void
    {
        $organization = Organization::create(['name' => 'Organização Protegida', 'status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        $target = User::factory()->create([
            'email' => 'novo.admin@example.com',
            'status' => 'active',
        ]);
        $this->grant($admin, $organization, 'access_manager', [AccessAbility::ACCESS_MANAGE]);

        $this->actingAs($admin)
            ->withSession(['active_organization_id' => $organization->id])
            ->post(route('access.store'), [
                'email' => 'novo.admin@example.com',
                'role' => 'access_manager_secondary',
                'abilities' => [AccessAbility::ACCESS_MANAGE],
                'expires_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('expires_at');

        $this->assertDatabaseMissing('user_organization_accesses', [
            'user_id' => $target->id,
            'organization_id' => $organization->id,
            'role' => 'access_manager_secondary',
        ]);
    }

    private function grant(
        User $user,
        Organization $organization,
        string $role,
        array $abilities,
        mixed $expiresAt = null,
    ): UserOrganizationAccess {
        return UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => $role,
            'abilities' => $abilities,
            'granted_at' => now(),
            'expires_at' => $expiresAt,
        ]);
    }

    private function asAdmin(User $user, Organization $organization): static
    {
        return $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);
    }
}
