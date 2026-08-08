<?php

namespace Tests\Feature;

use App\Models\AssessmentEvidence;
use App\Models\ExecutionEvent;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\User;
use App\Services\ExecutionAssessmentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class AssessmentRubricEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rubric_and_evidence_schema_exists(): void
    {
        $this->assertTrue(Schema::hasTable('assessment_criteria'));
        $this->assertTrue(Schema::hasColumns('assessment_criteria', [
            'id', 'uuid', 'execution_assessment_id', 'code', 'label', 'description',
            'weight', 'score', 'evaluator_notes', 'position', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('assessment_evidence'));
        $this->assertTrue(Schema::hasColumns('assessment_evidence', [
            'id', 'uuid', 'assessment_criterion_id', 'execution_event_id', 'statement',
            'observed_at', 'created_by_user_id', 'created_at', 'updated_at',
        ]));
    }

    public function test_assessment_seeds_learning_objectives_with_exact_total_weight(): void
    {
        $organization = $this->organization();
        $execution = $this->execution($organization, ['Comando', 'Comunicação', 'Triagem']);

        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);
        $assessment->load('criteria');

        $this->assertSame('m4', $assessment->source);
        $this->assertSame('70.00', $assessment->pass_threshold);
        $this->assertSame(['Comando', 'Comunicação', 'Triagem'], $assessment->criteria->pluck('label')->all());
        $this->assertSame(['33.33', '33.33', '33.34'], $assessment->criteria->pluck('weight')->all());
        $this->assertSame(100.00, round((float) $assessment->criteria->sum('weight'), 2));
    }

    public function test_assessment_without_learning_objectives_starts_with_empty_rubric(): void
    {
        $organization = $this->organization();
        $execution = $this->execution($organization, []);

        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);

        $this->assertCount(0, $assessment->criteria);
    }

    public function test_evidence_can_reference_only_event_from_same_execution(): void
    {
        $organization = $this->organization();
        $execution = $this->execution($organization, ['Comando']);
        $foreignExecution = $this->execution($organization, ['Outro objetivo'], 'Cenário paralelo');
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);
        $criterion = $assessment->criteria()->firstOrFail();
        $user = User::factory()->create(['status' => 'active']);

        $ownEvent = ExecutionEvent::create([
            'scenario_execution_id' => $execution->id,
            'kind' => 'observation',
            'occurred_at' => now()->subMinutes(2),
            'summary' => 'Comando estabelecido.',
        ]);

        $evidence = AssessmentEvidence::create([
            'assessment_criterion_id' => $criterion->id,
            'execution_event_id' => $ownEvent->id,
            'statement' => 'O comando foi estabelecido de forma objetiva.',
            'observed_at' => now()->subMinutes(2),
            'created_by_user_id' => $user->id,
        ]);

        $this->assertNotEmpty($evidence->uuid);

        $foreignEvent = ExecutionEvent::create([
            'scenario_execution_id' => $foreignExecution->id,
            'kind' => 'observation',
            'occurred_at' => now()->subMinute(),
            'summary' => 'Evento de outra execução.',
        ]);

        $this->expectException(InvalidArgumentException::class);

        AssessmentEvidence::create([
            'assessment_criterion_id' => $criterion->id,
            'execution_event_id' => $foreignEvent->id,
            'statement' => 'Referência indevida.',
            'observed_at' => now()->subMinute(),
            'created_by_user_id' => $user->id,
        ]);
    }

    private function organization(): Organization
    {
        return Organization::create([
            'name' => 'Centro M4 Rubrica',
            'kind' => 'company',
            'status' => 'active',
        ]);
    }

    private function execution(Organization $organization, array $objectives, string $title = 'Cenário M4'): ScenarioExecution
    {
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 3,
            'estimated_casualty_count' => 3,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => $objectives,
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
            'learning_objectives' => $objectives,
            'expected_actions' => $scenario->expected_actions,
            'critical_errors' => $scenario->critical_errors,
            'publication_status' => 'published',
        ]);

        return ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'running',
            'started_at' => now()->subMinutes(10),
        ]);
    }
}
