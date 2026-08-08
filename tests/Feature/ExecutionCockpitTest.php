<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ScenarioExecutionManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutionCockpitTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_page_exposes_publication_action_for_latest_draft_version(): void
    {
        [$organization, $scenario] = $this->scenarioWithVersion('draft');
        $this->authenticate($organization);

        $this->get(route('scenarios.show', $scenario))
            ->assertOk()
            ->assertSee('Publicar versão')
            ->assertDontSee('Nova execução');
    }

    public function test_published_scenario_page_exposes_new_execution_and_execution_history(): void
    {
        [$organization, $scenario, $version] = $this->scenarioWithVersion('published');
        $this->authenticate($organization);
        $execution = app(ScenarioExecutionManager::class)->create($version);

        $this->get(route('scenarios.show', $scenario))
            ->assertOk()
            ->assertSee('Nova execução')
            ->assertSee('Histórico de execuções')
            ->assertSee('Execução #'.$execution->sequence_number);
    }

    public function test_execution_cockpit_exposes_all_operational_modules(): void
    {
        [$organization, , $version] = $this->scenarioWithVersion('published');
        $this->authenticate($organization);
        $execution = app(ScenarioExecutionManager::class)->create($version);

        $this->get(route('executions.show', $execution))
            ->assertOk()
            ->assertSee('Timeline da execução')
            ->assertSee('Equipes e participantes')
            ->assertSee('Injects do instrutor')
            ->assertSee('Recursos');
    }

    private function scenarioWithVersion(string $publicationStatus): array
    {
        $organization = Organization::create([
            'name' => 'Centro Cockpit M3',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário Cockpit Premium',
            'environment' => 'Área industrial',
            'threat_level' => 'potencial',
            'casualties' => 30,
            'estimated_casualty_count' => 30,
            'mechanism' => 'Explosão simulada',
            'resources' => ['Kit IFAK', 'Rádio'],
            'learning_objectives' => ['Comando e controle'],
            'expected_actions' => ['Estabelecer zonas'],
            'critical_errors' => ['Entrada em área insegura'],
            'status' => 'draft',
        ]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => 30,
            'resources' => ['Kit IFAK', 'Rádio'],
            'learning_objectives' => ['Comando e controle'],
            'expected_actions' => ['Estabelecer zonas'],
            'critical_errors' => ['Entrada em área insegura'],
            'publication_status' => $publicationStatus,
        ]);

        return [$organization, $scenario, $version];
    }

    private function authenticate(Organization $organization): void
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'scenario_manager',
            'abilities' => [AccessAbility::SCENARIOS_VIEW, AccessAbility::SCENARIOS_MANAGE],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)->withSession(['active_organization_id' => $organization->id]);
    }
}
