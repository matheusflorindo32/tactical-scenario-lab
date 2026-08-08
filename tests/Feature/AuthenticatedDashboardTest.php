<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
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

    public function test_dashboard_only_aggregates_executions_from_active_organization(): void
    {
        [$user, $organization] = $this->userWithOrganization('active');
        $otherOrganization = Organization::create([
            'name' => 'Outra Organização',
            'kind' => 'company',
            'status' => 'active',
        ]);

        $visibleScenario = $this->scenario($organization, 'Cenário visível');
        $hiddenScenario = $this->scenario($otherOrganization, 'Cenário externo');
        $this->execution($organization, $this->version($visibleScenario), 1, 'running');
        $this->execution($otherOrganization, $this->version($hiddenScenario), 1, 'running');

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee($visibleScenario->title)
            ->assertDontSee($hiddenScenario->title);

        $this->assertSame(1, $response->viewData('running_count'));
        $this->assertSame(0, $response->viewData('completed_without_assessment_count'));
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
            'kind' => 'company',
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

    private function scenario(Organization $organization, string $title): Scenario
    {
        return Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Ambiente urbano',
            'threat_level' => 'potencial',
            'casualties' => 1,
            'estimated_casualty_count' => 1,
            'mechanism' => 'Trauma',
            'resources' => ['IFAK'],
            'learning_objectives' => ['Objetivo'],
            'expected_actions' => ['Ação'],
            'critical_errors' => ['Erro crítico'],
            'observed_critical_errors' => [],
            'status' => 'draft',
        ]);
    }

    private function version(Scenario $scenario): ScenarioVersion
    {
        return ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => 1,
            'resources' => $scenario->resources,
            'learning_objectives' => $scenario->learning_objectives,
            'expected_actions' => $scenario->expected_actions,
            'critical_errors' => $scenario->critical_errors,
            'publication_status' => 'published',
        ]);
    }

    private function execution(
        Organization $organization,
        ScenarioVersion $version,
        int $sequence,
        string $status,
    ): ScenarioExecution {
        return ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => $sequence,
            'status' => $status,
            'started_at' => $status === 'running' ? now()->subHour() : null,
        ]);
    }
}
