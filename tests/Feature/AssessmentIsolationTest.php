<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ExecutionAssessmentManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluation_manager_can_create_and_view_assessment(): void
    {
        $organization = $this->organization('M4 Ativa');
        $execution = $this->execution($organization);
        $this->authenticate($organization, [AccessAbility::SCENARIOS_VIEW, AccessAbility::EVALUATIONS_MANAGE]);

        $this->post(route('assessments.store', $execution))
            ->assertRedirect();

        $assessment = $execution->fresh()->assessment;
        $this->assertNotNull($assessment);

        $this->get(route('assessments.show', $assessment))
            ->assertOk();
    }

    public function test_scenario_manager_without_evaluation_ability_cannot_create_assessment(): void
    {
        $organization = $this->organization('M4 Gestor de Cenário');
        $execution = $this->execution($organization);
        $this->authenticate($organization, [AccessAbility::SCENARIOS_VIEW, AccessAbility::SCENARIOS_MANAGE]);

        $this->post(route('assessments.store', $execution))
            ->assertForbidden();

        $this->assertNull($execution->fresh()->assessment);
    }

    public function test_view_only_user_can_read_but_cannot_adjust_assessment(): void
    {
        $organization = $this->organization('M4 Somente Leitura');
        $execution = $this->execution($organization);
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);
        $this->authenticate($organization, [AccessAbility::SCENARIOS_VIEW]);

        $this->get(route('assessments.show', $assessment))->assertOk();
        $this->patch(route('assessments.adjustment', $assessment), [
            'evaluator_adjustment' => 2,
            'adjustment_justification' => 'Tentativa indevida.',
        ])->assertForbidden();

        $this->assertSame(0, $assessment->fresh()->evaluator_adjustment);
    }

    public function test_cross_organization_assessment_read_and_write_are_forbidden(): void
    {
        $active = $this->organization('M4 Organização Ativa');
        $external = $this->organization('M4 Organização Externa');
        $assessment = app(ExecutionAssessmentManager::class)
            ->createForExecution($this->execution($external));
        $this->authenticate($active, [AccessAbility::SCENARIOS_VIEW, AccessAbility::EVALUATIONS_MANAGE]);

        $this->get(route('assessments.show', $assessment))->assertForbidden();
        $this->patch(route('assessments.adjustment', $assessment), [
            'evaluator_adjustment' => 1,
            'adjustment_justification' => 'Cross-org.',
        ])->assertForbidden();

        $this->assertSame(0, $assessment->fresh()->evaluator_adjustment);
    }

    private function authenticate(Organization $organization, array $abilities): void
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'evaluator',
            'abilities' => $abilities,
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);
    }

    private function organization(string $name): Organization
    {
        return Organization::create([
            'name' => $name,
            'kind' => 'company',
            'status' => 'active',
        ]);
    }

    private function execution(Organization $organization): ScenarioExecution
    {
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário M4 '.$organization->id,
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 1,
            'estimated_casualty_count' => 1,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Comando'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha de segurança'],
            'status' => 'draft',
        ]);
        $version = $scenario->versions()->create([
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => $scenario->estimated_casualty_count,
            'resources' => $scenario->resources,
            'learning_objectives' => $scenario->learning_objectives,
            'expected_actions' => $scenario->expected_actions,
            'critical_errors' => $scenario->critical_errors,
            'publication_status' => 'published',
        ]);

        return ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subMinutes(20),
            'completed_at' => now(),
        ]);
    }
}
