<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganizationContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_initializes_first_active_organization_context(): void
    {
        $user = User::create([
            'name' => 'Operador Contextual',
            'email' => 'contexto@example.com',
            'password' => Hash::make('senha-segura'),
            'status' => 'active',
        ]);
        $organization = Organization::create([
            'name' => 'Organização Alfa',
            'kind' => 'military',
            'status' => 'active',
        ]);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'manager_org',
            'abilities' => ['people.view'],
            'granted_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'email' => 'contexto@example.com',
            'password' => 'senha-segura',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHas('active_organization_id', $organization->id);
    }

    public function test_user_can_switch_only_to_an_organization_with_active_access(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $allowed = Organization::create(['name' => 'Organização Permitida']);
        $denied = Organization::create(['name' => 'Organização Bloqueada']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $allowed->id,
            'role' => 'manager_org',
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $allowed->id])
            ->post(route('organizations.activate', $allowed))
            ->assertRedirect()
            ->assertSessionHas('active_organization_id', $allowed->id);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $allowed->id])
            ->post(route('organizations.activate', $denied))
            ->assertForbidden();

        $this->assertSame($allowed->id, session('active_organization_id'));
    }

    public function test_revoked_access_cannot_become_active_context(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $organization = Organization::create(['name' => 'Organização Revogada']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'manager_org',
            'granted_at' => now()->subDay(),
            'revoked_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('organizations.activate', $organization))
            ->assertForbidden();
    }
}
