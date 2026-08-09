<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\ExecutionDebrief;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ExecutionAssessmentManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M7AssessmentWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_assessment_renders_a_navigable_workbench_with_all_institutional_sections(): void
    {
        [$organization, $execution] = $this->context();
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);
        $this->authenticate($organization);

        $response = $this->get(route('assessments.show', $assessment))->assertOk();

        $response
            ->assertSee('data-assessment-state="draft"', false)
            ->assertSee('aria-label="Navegação da avaliação"', false)
            ->assertSee('href="#summary"', false)
            ->assertSee('href="#rubric"', false)
            ->assertSee('href="#critical-errors"', false)
            ->assertSee('href="#key-times"', false)
            ->assertSee('href="#debrief"', false)
            ->assertSee('href="#action-plan"', false)
            ->assertSee('href="#finalize"', false)
            ->assertSee('id="summary"', false)
            ->assertSee('id="rubric"', false)
            ->assertSee('id="critical-errors"', false)
            ->assertSee('id="key-times"', false)
            ->assertSee('id="debrief"', false)
            ->assertSee('id="action-plan"', false)
            ->assertSee('id="finalize"', false)
            ->assertSee('Prontidão para finalização');
    }

    public function test_finalized_assessment_is_visually_frozen_while_action_status_follow_up_remains_authorized(): void
    {
        [$organization, $execution] = $this->context();
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);
        $debrief = ExecutionDebrief::create(['execution_assessment_id' => $assessment->id]);
        $action = ActionItem::create([
            'execution_debrief_id' => $debrief->id,
            'action' => 'Revisar comunicação entre equipes.',
            'responsible_label' => 'Coordenação',
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        $assessment->update(['status' => 'finalized']);
        $this->authenticate($organization);

        $response = $this->get(route('assessments.show', $assessment))->assertOk();

        $response
            ->assertSee('data-assessment-state="finalized"', false)
            ->assertSee('Conteúdo histórico congelado')
            ->assertSee('id="action-plan"', false)
            ->assertSee(route('action-items.transition', $action), false)
            ->assertDontSee(route('assessments.finalize', $assessment), false)
            ->assertDontSee('id="finalize"', false);
    }

    private function authenticate(Organization $organization): void
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'm7_evaluator',
            'abilities' => [
                AccessAbility::SCENARIOS_VIEW,
                AccessAbility::EVALUATIONS_MANAGE,
            ],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);
    }

    private function context(): array
    {
        $organization = Organization::create([
            'name' => 'Centro M7 Assessment '.fake()->uuid(),
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Avaliação Operacional M7',
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 2,
            'estimated_casualty_count' => 2,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Comando', 'Comunicação'],
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
        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);

        return [$organization, $execution];
    }
}
