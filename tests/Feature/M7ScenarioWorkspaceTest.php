<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M7ScenarioWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_index_explains_version_lifecycle_without_presenting_legacy_score_as_assessment_truth(): void
    {
        [$organization, $user] = $this->institutionalUser();
        $scenario = $this->scenario($organization, 'Cenário Legado Visível');
        $scenario->forceFill([
            'status' => 'completed',
            'score' => 97,
        ])->save();
        $this->version($scenario, 'published');

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('scenarios.index'))
            ->assertOk();

        $response
            ->assertSee('Ciclo do cenário')
            ->assertSee('Rascunho')
            ->assertSee('Publicado')
            ->assertSee('Preparar')
            ->assertSee('Executar')
            ->assertSee('Avaliar')
            ->assertDontSee('Média das avaliações')
            ->assertDontSee('97/100');
    }

    public function test_template_library_marks_templates_as_the_current_canonical_navigation(): void
    {
        [$organization, $user] = $this->institutionalUser();

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('scenario-templates.index'))
            ->assertOk();

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('scenario-templates.index'), '/').'"[^>]*aria-current="page"/',
            $response->getContent(),
        );
    }

    public function test_execution_history_uses_history_navigation_and_accessible_table_primitive(): void
    {
        [$organization, $user] = $this->institutionalUser();
        $scenario = $this->scenario($organization, 'Histórico Operacional M7');
        $version = $this->version($scenario, 'published');

        ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('execution-history.index'))
            ->assertOk()
            ->assertSee('aria-label="Histórico de execuções"', false);

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('execution-history.index'), '/').'"[^>]*aria-current="page"/',
            $response->getContent(),
        );
    }

    private function institutionalUser(): array
    {
        $organization = Organization::create([
            'name' => 'Centro M7 Cenários',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'm7_scenario_operator',
            'abilities' => AccessAbility::all(),
            'granted_at' => now(),
        ]);

        return [$organization, $user];
    }

    private function scenario(Organization $organization, string $title): Scenario
    {
        return Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 12,
            'estimated_casualty_count' => 12,
            'mechanism' => 'Simulação institucional',
            'resources' => ['Rádio', 'IFAK'],
            'learning_objectives' => ['Comando e controle'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Perda de comunicação'],
            'status' => 'draft',
        ]);
    }

    private function version(Scenario $scenario, string $publicationStatus): ScenarioVersion
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
            'publication_status' => $publicationStatus,
        ]);
    }
}
