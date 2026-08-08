<?php

namespace Tests\Feature;

use App\Models\ExecutionAssessment;
use App\Models\ScenarioExecution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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
}
