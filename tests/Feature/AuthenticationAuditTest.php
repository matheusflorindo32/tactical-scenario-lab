<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_and_logout_are_audited_without_credentials(): void
    {
        $organization = Organization::create([
            'name' => 'Organização Auditoria Auth',
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Operador Auditável',
            'email' => 'auth.audit@example.com',
            'password' => Hash::make('senha-super-secreta'),
            'status' => 'active',
        ]);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'test_access',
            'abilities' => ['scenarios.view'],
            'granted_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'email' => 'AUTH.AUDIT@EXAMPLE.COM',
            'password' => 'senha-super-secreta',
        ])->assertRedirect(route('dashboard'));

        $login = AuditLog::query()
            ->where('action', 'auth.login.succeeded')
            ->sole();

        $this->assertSame('user', $login->actor_type);
        $this->assertSame($user->id, $login->actor_id);
        $this->assertSame($organization->id, $login->organization_id);
        $this->assertSame(User::class, $login->subject_type);
        $this->assertSame($user->id, $login->subject_id);
        $this->assertSame(['organization_context_initialized' => true], $login->payload);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $logout = AuditLog::query()
            ->where('action', 'auth.logout')
            ->sole();

        $this->assertSame('user', $logout->actor_type);
        $this->assertSame($user->id, $logout->actor_id);
        $this->assertSame($organization->id, $logout->organization_id);
        $this->assertSame(['organization_context_present' => true], $logout->payload);

        $auditJson = AuditLog::query()->get()->toJson();
        $this->assertStringNotContainsString('senha-super-secreta', $auditJson);
        $this->assertStringNotContainsString('auth.audit@example.com', $auditJson);
    }

    public function test_failed_login_does_not_create_success_audit_event(): void
    {
        User::create([
            'name' => 'Conta de Teste',
            'email' => 'falha@example.com',
            'password' => Hash::make('senha-correta'),
            'status' => 'active',
        ]);

        $this->post(route('login.store'), [
            'email' => 'falha@example.com',
            'password' => 'senha-errada',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'auth.login.succeeded',
        ]);
    }
}
