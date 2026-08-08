<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssessmentRubricEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rubric_and_evidence_schema_exists(): void
    {
        $this->assertTrue(Schema::hasTable('assessment_criteria'));
        $this->assertTrue(Schema::hasColumns('assessment_criteria', [
            'id',
            'uuid',
            'execution_assessment_id',
            'code',
            'label',
            'description',
            'weight',
            'score',
            'evaluator_notes',
            'position',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('assessment_evidence'));
        $this->assertTrue(Schema::hasColumns('assessment_evidence', [
            'id',
            'uuid',
            'assessment_criterion_id',
            'execution_event_id',
            'statement',
            'observed_at',
            'created_by_user_id',
            'created_at',
            'updated_at',
        ]));
    }
}
