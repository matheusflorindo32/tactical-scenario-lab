<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssessmentObservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_critical_error_and_key_time_schema_exists(): void
    {
        $this->assertTrue(Schema::hasTable('critical_error_occurrences'));
        $this->assertTrue(Schema::hasColumns('critical_error_occurrences', [
            'id', 'uuid', 'execution_assessment_id', 'catalog_label_snapshot', 'rule',
            'penalty_points', 'execution_event_id', 'observed_at', 'notes', 'source',
            'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('key_time_records'));
        $this->assertTrue(Schema::hasColumns('key_time_records', [
            'id', 'uuid', 'execution_assessment_id', 'label', 'occurred_at',
            'elapsed_seconds', 'reference_seconds', 'notes', 'created_at', 'updated_at',
        ]));
    }
}
