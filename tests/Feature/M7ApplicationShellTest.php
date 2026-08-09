<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M7ApplicationShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_shell_has_one_canonical_navigation_with_real_operational_routes(): void
    {
        [$organization, $user] = $this->institutionalUser();

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertOk();

        $html = $response->getContent();

        $response
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertSee('href="'.route('scenarios.index').'"', false)
            ->assertSee('href="'.route('scenario-templates.index').'"', false)
            ->assertSee('href="'.route('execution-history.index').'"', false)
            ->assertSee('href="'.route('people.index').'"', false)
            ->assertSee('href="'.route('organizations.index').'"', false)
            ->assertSee('href="'.route('access.index').'"', false)
            ->assertDontSee('href="#"', false)
            ->assertDontSee('Guias')
            ->assertDontSee('Referência');

        $this->assertSame(1, substr_count($html, 'aria-label="Navegação principal"'));
        $this->assertSame(0, substr_count($html, 'aria-label="Principal"'));
    }

    public function test_dashboard_marks_painel_as_current_and_preserves_accessibility_shell_contracts(): void
    {
        [$organization, $user] = $this->institutionalUser();

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertOk();

        $html = $response->getContent();

        $response
            ->assertSee('href="#main"', false)
            ->assertSee('aria-label="Abrir menu"', false)
            ->assertSee($organization->name);

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('dashboard'), '/').'"[^>]*aria-current="page"/',
            $html,
        );
    }

    private function institutionalUser(): array
    {
        $organization = Organization::create([
            'name' => 'Centro Operacional M7',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'm7_operator',
            'abilities' => AccessAbility::all(),
            'granted_at' => now(),
        ]);

        return [$organization, $user];
    }
}
