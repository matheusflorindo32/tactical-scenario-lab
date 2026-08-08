<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_available(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Entrar no sistema');
    }

    public function test_active_user_can_authenticate_with_normalized_email_and_logout(): void
    {
        $user = User::create([
            'name' => 'Operador Diamante',
            'email' => 'operador@example.com',
            'password' => Hash::make('senha-segura'),
            'status' => 'active',
        ]);

        $this->post(route('login.store'), [
            'email' => '  OPERADOR@EXAMPLE.COM ',
            'password' => 'senha-segura',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        User::create([
            'name' => 'Conta Inativa',
            'email' => 'inativo@example.com',
            'password' => Hash::make('senha-segura'),
            'status' => 'inactive',
        ]);

        $this->post(route('login.store'), [
            'email' => 'inativo@example.com',
            'password' => 'senha-segura',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_access_is_scoped_to_an_organization(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $organization = Organization::create([
            'name' => 'Organização Diamante',
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

        $this->assertTrue($user->hasOrganizationAccess($organization->id));
        $this->assertSame(['people.view'], $user->activeOrganizationAccesses()->firstOrFail()->abilities);
    }
}
