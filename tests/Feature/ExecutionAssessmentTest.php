<?php

namespace Tests\Feature;

use App\Models\ExecutionAssessment;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class ExecutionAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_assessment_schema_exists_with_lifecycle_and_scoring_snapshot_fields(): void
    {
        $this->assertTrue(Schema::hasTable('execution_assessments'));
        $this->assertTrue(Schema::hasColumns('execution_assessments', [
            'id',
            'uuid',
            'organization_id',
            'scenario_execution_id',
            'source',
            'status',
            'pass_threshold',
            'base_score',
            'penalty_points',
            'evaluator_adjustment',
            'adjustment_justification',
            'final_score',
            'result',
            'automatic_fail',
            'finalized_at',
            'finalized_by_user_id',
            'legacy_imported_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_execution_assessment_domain_model_and_execution_relation_exist(): void
    {
        $this->assertTrue(class_exists(ExecutionAssessment::class));
        $this->assertTrue(method_exists(ScenarioExecution::class, 'assessment'));
    }

    public function test_assessment_has_public_uuid_lifecycle_and_execution_relation(): void
    {
        $execution = $this->execution();

        $assessment = ExecutionAssessment::create([
            'organization_id' => $execution->organization_id,
            'scenario_execution_id' => $execution->id,
            'source' => 'm4',
            'status' => 'draft',
            'pass_threshold' => 70.00,
        ]);

        $this->assertNotEmpty($assessment->uuid);
        $this->assertTrue($assessment->isDraft());
        $this->assertFalse($assessment->isFinalized());
        $this->assertSame($assessment->id, $execution->fresh()->assessment->id);
        $this->assertSame($execution->id, $assessment->execution->id);
        $this->assertSame('70.00', $assessment->pass_threshold);

        $assessment->update(['status' => 'finalized']);
        $assessment->refresh();

        $this->assertFalse($assessment->isDraft());
        $this->assertTrue($assessment->isFinalized());
    }

    public function test_execution_cannot_have_two_assessments(): void
    {
        $execution = $this->execution();

        ExecutionAssessment::create([
            'organization_id' => $execution->organization_id,
            'scenario_execution_id' => $execution->id,
            'source' => 'm4',
            'status' => 'draft',
            'pass_threshold' => 70.00,
        ]);

        $this->expectException(QueryException::class);

        ExecutionAssessment::create([
            'organization_id' => $execution->organization_id,
            'scenario_execution_id' => $execution->id,
            'source' => 'm4',
            'status' => 'draft',
            'pass_threshold' => 70.00,
        ]);
    }

    public function test_assessment_organization_must_match_execution_organization(): void
    {
        $execution = $this->execution();
        $foreignOrganization = Organization::create([
            'name' => 'Outra organização M4',
            'kind' => 'company',
            'status' => 'active',
        ]);

        $this->expectException(InvalidArgumentException::class);

        ExecutionAssessment::create([
            'organization_id' => $foreignOrganization->id,
            'scenario_execution_id' => $execution->id,
            'source' => 'm4',
            'status' => 'draft',
            'pass_threshold' => 70.00,
        ]);
    }

    private function execution(): ScenarioExecution
    {
        $organization = Organization::create([
            'name' => 'Centro de Avaliação M4',
            'kind' => 'company',
            'status' => 'active',
        ]);

        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Avaliação institucional',
            'environment' => 'Área de treinamento',
            'threat_level' => 'controlada',
            'casualties' => 2,
            'estimated_casualty_count' => 2,
            'mechanism' => 'Simulação operacional',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Comunicação'],
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

        return ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
    }
}
