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

class AssessmentCockpitTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_cockpit_exposes_assessment_and_debriefing_entry_point(): void
    {
        [$organization, $execution] = $this->context();
        $this->authenticate($organization);

        $this->get(route('executions.show', $execution))
            ->assertOk()
            ->assertSee('Avaliação & Debriefing');
    }

    public function test_assessment_page_explains_full_structured_evaluation_model(): void
    {
        [$organization, $execution] = $this->context();
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);
        $this->authenticate($organization);

        $this->get(route('assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Nota-base')
            ->assertSee('Penalidades')
            ->assertSee('Ajuste do avaliador')
            ->assertSee('Nota final')
            ->assertSee('Rubrica')
            ->assertSee('Erros críticos observados')
            ->assertSee('Tempos-chave')
            ->assertSee('Fatos')
            ->assertSee('Interpretações')
            ->assertSee('Recomendações')
            ->assertSee('Plano de ação');
    }

    private function authenticate(Organization $organization): void
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'evaluator',
            'abilities' => [AccessAbility::SCENARIOS_VIEW, AccessAbility::EVALUATIONS_MANAGE],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);
    }

    private function context(): array
    {
        $organization = Organization::create([
            'name' => 'Centro M4 Cockpit',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário M4 Cockpit',
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
        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subMinutes(20),
            'completed_at' => now(),
        ]);

        return [$organization, $execution];
    }
}
