<?php

namespace Tests\Feature;

use App\Http\Controllers\ScenarioController;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\LegacyAssessmentImporter;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LegacyAssessmentMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_assessment_is_imported_to_execution_one_without_invented_semantics(): void
    {
        [$scenario, $execution] = $this->legacyContext();

        app(LegacyAssessmentImporter::class)->import();

        $assessment = $execution->fresh()->assessment;

        $this->assertNotNull($assessment);
        $this->assertSame('legacy', $assessment->source);
        $this->assertSame('finalized', $assessment->status);
        $this->assertSame('82.00', $assessment->base_score);
        $this->assertSame('82.00', $assessment->final_score);
        $this->assertNull($assessment->pass_threshold);
        $this->assertNull($assessment->result);
        $this->assertFalse($assessment->automatic_fail);
        $this->assertNotNull($assessment->legacy_imported_at);

        $criterion = $assessment->criteria()->firstOrFail();
        $this->assertSame('Avaliação legada importada', $criterion->label);
        $this->assertSame('100.00', $criterion->weight);
        $this->assertSame('82.00', $criterion->score);

        $evidence = $criterion->evidence()->firstOrFail();
        $this->assertSame(
            'Valor numérico importado do registro de avaliação legado do cenário.',
            $evidence->statement,
        );
        $this->assertNull($evidence->execution_event_id);
        $this->assertNull($evidence->created_by_user_id);

        $occurrences = $assessment->criticalErrorOccurrences()->get();
        $this->assertEqualsCanonicalizing(
            ['Erro legado fora do catálogo atual', 'Falha de segurança'],
            $occurrences->pluck('catalog_label_snapshot')->all(),
        );
        $this->assertTrue($occurrences->every(fn ($occurrence): bool => $occurrence->source === 'legacy'));
        $this->assertTrue($occurrences->every(fn ($occurrence): bool => $occurrence->rule === 'record'));
        $this->assertTrue($occurrences->every(fn ($occurrence): bool => $occurrence->penalty_points === '0.00'));

        $debrief = $assessment->debrief()->firstOrFail();
        $entry = $debrief->entries()->sole();
        $this->assertSame('legacy_unstructured', $entry->kind);
        $this->assertSame('Debrief histórico sem estrutura semântica.', $entry->content);

        $this->assertSame(82, $scenario->fresh()->score);
        $this->assertSame('Debrief histórico sem estrutura semântica.', $scenario->fresh()->debrief_notes);
    }

    public function test_importer_is_idempotent(): void
    {
        [, $execution] = $this->legacyContext();
        $importer = app(LegacyAssessmentImporter::class);

        $importer->import();
        $assessmentId = $execution->fresh()->assessment->id;
        $importer->import();

        $this->assertSame($assessmentId, $execution->fresh()->assessment->id);
        $this->assertDatabaseCount('execution_assessments', 1);
        $this->assertDatabaseCount('assessment_criteria', 1);
        $this->assertDatabaseCount('assessment_evidence', 1);
        $this->assertDatabaseCount('critical_error_occurrences', 2);
        $this->assertDatabaseCount('debrief_entries', 1);
    }

    public function test_legacy_data_without_execution_mapping_is_left_untouched(): void
    {
        $organization = Organization::create([
            'name' => 'Legado sem execução',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = $this->scenario($organization, 'Sem execução');
        $scenario->update([
            'score' => 65,
            'debrief_notes' => 'Não mapear por heurística.',
            'observed_critical_errors' => ['Falha de segurança'],
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        app(LegacyAssessmentImporter::class)->import();

        $this->assertDatabaseCount('execution_assessments', 0);
        $this->assertSame(65, $scenario->fresh()->score);
        $this->assertSame('Não mapear por heurística.', $scenario->fresh()->debrief_notes);
    }

    public function test_legacy_evaluation_http_path_is_retired_after_m4_activation(): void
    {
        $this->assertFalse(Route::has('scenarios.evaluate'));
        $this->assertFalse(method_exists(ScenarioController::class, 'evaluate'));
    }

    public function test_completed_legacy_scenario_page_renders_without_retired_evaluation_form(): void
    {
        [$scenario] = $this->legacyContext();
        $this->authenticateViewer($scenario->organization);

        $this->get(route('scenarios.show', $scenario))
            ->assertOk()
            ->assertDontSee('name="score"', false)
            ->assertDontSee('name="debrief_notes"', false)
            ->assertDontSee('observed_critical_errors[]', false);
    }

    private function authenticateViewer(Organization $organization): void
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'viewer',
            'abilities' => [AccessAbility::SCENARIOS_VIEW],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);
    }

    private function legacyContext(): array
    {
        $organization = Organization::create([
            'name' => 'Centro Legado M4 '.fake()->uuid(),
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = $this->scenario($organization, 'Cenário legado');
        $scenario->update([
            'score' => 82,
            'debrief_notes' => 'Debrief histórico sem estrutura semântica.',
            'observed_critical_errors' => ['Falha de segurança', 'Erro legado fora do catálogo atual'],
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);
        $version = $scenario->latestVersion()->firstOrFail();
        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => $scenario->started_at,
            'completed_at' => $scenario->completed_at,
        ]);

        return [$scenario, $execution];
    }

    private function scenario(Organization $organization, string $title): Scenario
    {
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
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

        $scenario->versions()->create([
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

        return $scenario;
    }
}
