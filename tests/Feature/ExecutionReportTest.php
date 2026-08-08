<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonContact;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Reporting\ExecutionReportDataBuilder;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExecutionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_execution_exposes_organization_relation_to_report_runtime(): void
    {
        [, $execution] = $this->executionContext('Diagnóstico Relação');

        $this->assertTrue(
            method_exists($execution, 'organization'),
            'ScenarioExecution runtime methods: '.implode(', ', get_class_methods($execution)),
        );
        $this->assertInstanceOf(Organization::class, $execution->organization()->firstOrFail());
    }

    public function test_report_builder_is_presentation_safe_and_omits_contact_pii(): void
    {
        [$organization, $execution] = $this->executionContext('Relatório Seguro');
        $person = Person::create([
            'display_name' => 'Participante Fictício PDF',
            'status' => 'active',
        ]);
        PersonContact::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'type' => 'email',
            'value' => 'segredo.pdf@example.test',
            'label' => 'Privado',
            'is_primary' => true,
        ]);
        DB::table('execution_participants')->insert([
            'uuid' => (string) Str::uuid(),
            'scenario_execution_id' => $execution->id,
            'person_id' => $person->id,
            'unit_name_snapshot' => 'Unidade PDF',
            'position_snapshot' => 'Instrutor',
            'role' => 'Líder',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = app(ExecutionReportDataBuilder::class)->build($execution, $organization->id);
        $serialized = json_encode($report, JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('Participante Fictício PDF', $serialized);
        $this->assertStringContainsString('Unidade PDF', $serialized);
        $this->assertStringNotContainsString('segredo.pdf@example.test', $serialized);
        $this->assertStringNotContainsString('contacts', strtolower($serialized));
        $this->assertStringNotContainsString('identifiers', strtolower($serialized));
    }

    public function test_pdf_endpoint_requires_reports_view_and_returns_application_pdf(): void
    {
        [$organization, $execution] = $this->executionContext('Relatório HTTP');
        $unauthorized = $this->user($organization, [AccessAbility::SCENARIOS_VIEW]);
        $authorized = $this->user($organization, [AccessAbility::REPORTS_VIEW]);

        $this->actingAs($unauthorized)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/reports/executions/'.$execution->uuid.'/pdf')
            ->assertForbidden();

        $response = $this->actingAs($authorized)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/reports/executions/'.$execution->uuid.'/pdf');

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=execution-'.$execution->uuid.'.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    private function executionContext(string $title): array
    {
        $organization = Organization::create([
            'name' => 'Organização '.$title,
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
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
        $version = ScenarioVersion::create([
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
        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        return [$organization, $execution];
    }

    private function user(Organization $organization, array $abilities): User
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'reporter',
            'abilities' => $abilities,
            'granted_at' => now(),
        ]);

        return $user;
    }
}
