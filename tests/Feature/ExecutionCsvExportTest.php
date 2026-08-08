<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Reporting\ExecutionCsvExporter;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutionCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_header_order_and_formula_neutralization_are_stable(): void
    {
        $exporter = app(ExecutionCsvExporter::class);

        $this->assertSame([
            'execution_uuid',
            'execution_sequence',
            'scenario_uuid',
            'scenario_title',
            'scenario_version',
            'unit_uuids',
            'unit_names',
            'execution_status',
            'started_at',
            'completed_at',
            'assessment_status',
            'final_score',
            'result',
            'automatic_fail',
            'critical_error_count',
            'open_action_count',
        ], $exporter->headers());
        $this->assertSame("'=cmd", $exporter->neutralizeForSpreadsheet('=cmd'));
        $this->assertSame("'+SUM(A1:A2)", $exporter->neutralizeForSpreadsheet('+SUM(A1:A2)'));
        $this->assertSame('Normal', $exporter->neutralizeForSpreadsheet('Normal'));
    }

    public function test_csv_requires_reports_view_and_exports_only_active_organization(): void
    {
        [$authorized, $organization] = $this->userContext([AccessAbility::REPORTS_VIEW]);
        [$unauthorized] = $this->userContext([AccessAbility::SCENARIOS_VIEW], $organization);
        $scenario = $this->scenario($organization, '=Cenário Fórmula');
        $execution = $this->execution($organization, $this->version($scenario));

        $foreign = Organization::create(['name' => 'CSV Externo', 'kind' => 'company', 'status' => 'active']);
        $foreignScenario = $this->scenario($foreign, 'Não exportar');
        $this->execution($foreign, $this->version($foreignScenario));

        $this->actingAs($unauthorized)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/reports/executions.csv')
            ->assertForbidden();

        $response = $this->actingAs($authorized)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/reports/executions.csv');

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('execution_uuid,execution_sequence,scenario_uuid', $csv);
        $this->assertStringContainsString($execution->uuid, $csv);
        $this->assertStringContainsString("'=Cenário Fórmula", $csv);
        $this->assertStringNotContainsString('Não exportar', $csv);
    }

    private function userContext(array $abilities, ?Organization $organization = null): array
    {
        $organization ??= Organization::create(['name' => 'CSV M5', 'kind' => 'company', 'status' => 'active']);
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'csv',
            'abilities' => $abilities,
            'granted_at' => now(),
        ]);

        return [$user, $organization];
    }

    private function scenario(Organization $organization, string $title): Scenario
    {
        return Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 1,
            'estimated_casualty_count' => 1,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Coordenação'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha'],
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

    private function execution(Organization $organization, ScenarioVersion $version): ScenarioExecution
    {
        return ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
