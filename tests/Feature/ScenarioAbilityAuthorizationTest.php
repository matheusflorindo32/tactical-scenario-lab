<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScenarioAbilityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_only_access_can_read_but_cannot_manage_or_evaluate(): void
    {
        [$organization, $scenario] = $this->context();
        $this->authenticate($organization, ['scenarios.view']);

        $this->get(route('scenarios.index'))->assertOk();
        $this->get(route('scenarios.show', $scenario))->assertOk();
        $this->get(route('scenarios.create'))->assertForbidden();
        $this->post(route('scenarios.store'), [])->assertForbidden();
        $this->post(route('scenarios.execute', $scenario))->assertForbidden();
        $this->post(route('scenarios.evaluate', $scenario), ['score' => 80])->assertForbidden();
    }

    public function test_scenario_manager_can_create_and_execute_but_cannot_evaluate_without_evaluation_ability(): void
    {
        [$organization, $scenario] = $this->context();
        $this->authenticate($organization, ['scenarios.view', 'scenarios.manage']);

        $this->get(route('scenarios.create'))->assertOk();
        $this->post(route('scenarios.execute', $scenario))->assertRedirect();

        $scenario->refresh();
        $this->assertSame('running', $scenario->status);

        $this->post(route('scenarios.evaluate', $scenario), ['score' => 90])->assertForbidden();
        $this->assertSame('running', $scenario->refresh()->status);
    }

    public function test_evaluator_can_complete_running_scenario_with_evaluation_ability(): void
    {
        [$organization, $scenario] = $this->context('running');
        $this->authenticate($organization, ['scenarios.view', 'evaluations.manage']);

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => 95,
            'observed_critical_errors' => [],
        ])->assertRedirect();

        $scenario->refresh();
        $this->assertSame('completed', $scenario->status);
        $this->assertSame(95, $scenario->score);
    }

    private function authenticate(Organization $organization, array $abilities): void
    {
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'test_access',
            'abilities' => $abilities,
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);
    }

    private function context(string $status = 'draft'): array
    {
        $organization = Organization::create([
            'name' => 'Instituição Cenários',
            'status' => 'active',
        ]);

        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário de autorização',
            'environment' => 'Ambiente urbano',
            'threat_level' => 'potencial',
            'casualties' => 1,
            'mechanism' => 'Trauma penetrante',
            'resources' => ['IFAK'],
            'learning_objectives' => ['Validar autorização'],
            'expected_actions' => ['Executar conduta esperada'],
            'critical_errors' => ['Erro crítico'],
            'status' => $status,
            'started_at' => $status === 'running' ? now() : null,
        ]);

        return [$organization, $scenario];
    }
}
