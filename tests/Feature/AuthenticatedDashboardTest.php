<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_opening_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_active_user_can_open_dashboard_with_valid_context(): void
    {
        [$user, $organization] = $this->userWithOrganization('active');

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_only_aggregates_scenarios_from_active_organization(): void
    {
        [$user, $organization] = $this->userWithOrganization('active');
        $otherOrganization = Organization::create([
            'name' => 'Outra Organização',
            'status' => 'active',
        ]);

        $visibleScenario = $this->scenario($organization, 'Cenário visível', 'completed', 90);
        $hiddenScenario = $this->scenario($otherOrganization, 'Cenário externo', 'completed', 10);

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee($visibleScenario->title)
            ->assertDontSee($hiddenScenario->title);

        $this->assertSame(1, $response->viewData('total'));
        $this->assertSame(1, $response->viewData('completed'));
        $this->assertEquals(90.0, $response->viewData('avgScore'));
    }

    public function test_inactive_account_is_logged_out_and_blocked_even_with_existing_session(): void
    {
        [$user, $organization] = $this->userWithOrganization('inactive');

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    private function userWithOrganization(string $status): array
    {
        $organization = Organization::create([
            'name' => 'Organização Dashboard',
            'status' => 'active',
        ]);

        $user = User::factory()->create(['status' => $status]);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'dashboard_access',
            'abilities' => ['scenarios.view'],
            'granted_at' => now(),
        ]);

        return [$user, $organization];
    }

    private function scenario(
        Organization $organization,
        string $title,
        string $status,
        ?int $score,
    ): Scenario {
        return Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Ambiente urbano',
            'threat_level' => 'potencial',
            'casualties' => 1,
            'mechanism' => 'Trauma',
            'resources' => ['IFAK'],
            'learning_objectives' => ['Objetivo'],
            'expected_actions' => ['Ação'],
            'critical_errors' => ['Erro crítico'],
            'observed_critical_errors' => [],
            'status' => $status,
            'score' => $score,
            'started_at' => $status !== 'draft' ? now() : null,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);
    }
}
