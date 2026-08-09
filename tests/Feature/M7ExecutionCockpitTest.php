<?php

namespace Tests\Feature;

use App\Models\ExecutionEvent;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ExecutionAssessmentManager;
use App\Services\ScenarioExecutionManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M7ExecutionCockpitTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_execution_is_presented_as_an_operational_cockpit_with_primary_timeline(): void
    {
        [$organization, , $version] = $this->scenarioWithPublishedVersion();
        $this->authenticate($organization);

        $execution = app(ScenarioExecutionManager::class)->create($version);
        app(ScenarioExecutionManager::class)->start($execution);
        $execution = $execution->fresh();

        ExecutionEvent::create([
            'scenario_execution_id' => $execution->id,
            'kind' => 'observation',
            'occurred_at' => now(),
            'summary' => 'Equipe estabeleceu comando inicial.',
        ]);

        $response = $this->get(route('executions.show', $execution))->assertOk();
        $html = $response->getContent();

        $response
            ->assertSee('data-cockpit-region="lifecycle"', false)
            ->assertSee('aria-label="Navegação do cockpit"', false)
            ->assertSee('href="#timeline"', false)
            ->assertSee('href="#teams"', false)
            ->assertSee('href="#resources"', false)
            ->assertSee('href="#injects"', false)
            ->assertSee('href="#assessment"', false)
            ->assertSee('id="timeline"', false)
            ->assertSee('data-cockpit-region="timeline"', false)
            ->assertSee('data-history-mode="append-only"', false)
            ->assertSee('Equipe estabeleceu comando inicial.')
            ->assertSee('Concluir execução')
            ->assertSee('Cancelar');

        $this->assertLessThan(
            strpos($html, 'id="teams"'),
            strpos($html, 'id="timeline"'),
            'Timeline must be visually ordered before team configuration in the operational cockpit.',
        );
    }

    public function test_completed_execution_keeps_assessment_entry_clear_and_historical_events_non_mutable(): void
    {
        [$organization, , $version] = $this->scenarioWithPublishedVersion();
        $this->authenticate($organization);

        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subMinutes(20),
            'completed_at' => now(),
        ]);
        ExecutionEvent::create([
            'scenario_execution_id' => $execution->id,
            'kind' => 'system',
            'occurred_at' => now()->subMinutes(10),
            'summary' => 'Marco histórico preservado.',
        ]);
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);

        $response = $this->get(route('executions.show', $execution))->assertOk();

        $response
            ->assertSee('id="assessment"', false)
            ->assertSee(route('assessments.show', $assessment), false)
            ->assertSee('Registro histórico · somente acréscimo')
            ->assertDontSee('Editar evento')
            ->assertDontSee('Excluir evento');
    }

    private function scenarioWithPublishedVersion(): array
    {
        $organization = Organization::create([
            'name' => 'Centro M7 Cockpit',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Operação Horizonte M7',
            'environment' => 'Complexo industrial',
            'threat_level' => 'potencial',
            'casualties' => 8,
            'estimated_casualty_count' => 8,
            'mechanism' => 'Explosão simulada',
            'resources' => ['Rádio', 'IFAK'],
            'learning_objectives' => ['Comando e controle'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Entrada em área insegura'],
            'status' => 'draft',
        ]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
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

        return [$organization, $scenario, $version];
    }

    private function authenticate(Organization $organization): void
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'm7_cockpit_operator',
            'abilities' => [
                AccessAbility::SCENARIOS_VIEW,
                AccessAbility::SCENARIOS_MANAGE,
                AccessAbility::EVALUATIONS_MANAGE,
            ],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)->withSession(['active_organization_id' => $organization->id]);
    }
}
