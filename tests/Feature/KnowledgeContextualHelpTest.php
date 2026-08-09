<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ExecutionAssessmentManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeContextualHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_and_history_surfaces_link_to_their_exact_product_guides(): void
    {
        [$organization, $user] = $this->institutionalUser();
        $this->actingAs($user)->withSession(['active_organization_id' => $organization->id]);

        $scenario = $this->scenario($organization);
        $version = $this->version($scenario);
        ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->assertContextualGuide(
            $this->get(route('scenarios.index'))->assertOk(),
            'scenarios-and-versioning',
        );
        $this->assertContextualGuide(
            $this->get(route('execution-history.index'))->assertOk(),
            'history-and-reports',
        );
    }

    public function test_execution_and_assessment_surfaces_link_to_their_exact_product_guides(): void
    {
        [$organization, $user] = $this->institutionalUser();
        $this->actingAs($user)->withSession(['active_organization_id' => $organization->id]);

        $scenario = $this->scenario($organization);
        $version = $this->version($scenario);
        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);

        $this->assertContextualGuide(
            $this->get(route('executions.show', $execution))->assertOk(),
            'execution-cockpit',
        );
        $this->assertContextualGuide(
            $this->get(route('assessments.show', $assessment))->assertOk(),
            'assessment-and-debrief',
        );
    }

    public function test_management_indexes_share_the_governance_guide_without_tenant_parameters(): void
    {
        [$organization, $user] = $this->institutionalUser();
        $this->actingAs($user)->withSession(['active_organization_id' => $organization->id]);

        foreach (['people.index', 'organizations.index', 'access.index'] as $routeName) {
            $this->assertContextualGuide(
                $this->get(route($routeName))->assertOk(),
                'people-organizations-access',
            );
        }
    }

    private function assertContextualGuide($response, string $slug): void
    {
        $response
            ->assertSee('Como usar esta tela')
            ->assertSee('href="'.route('knowledge.show', $slug).'"', false)
            ->assertDontSee('organization_id=', false)
            ->assertDontSee('active_organization_id=', false);
    }

    private function institutionalUser(): array
    {
        $organization = Organization::create([
            'name' => 'Centro Contextual M8',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'm8_operator',
            'abilities' => AccessAbility::all(),
            'granted_at' => now(),
        ]);

        return [$organization, $user];
    }

    private function scenario(Organization $organization): Scenario
    {
        return Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário Contextual M8',
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 2,
            'estimated_casualty_count' => 2,
            'mechanism' => 'Simulação institucional',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Comando'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha de comunicação'],
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
            'estimated_casualty_count' => $scenario->estimated_casualty_count,
            'resources' => $scenario->resources,
            'learning_objectives' => $scenario->learning_objectives,
            'expected_actions' => $scenario->expected_actions,
            'critical_errors' => $scenario->critical_errors,
            'publication_status' => 'published',
        ]);
    }
}
