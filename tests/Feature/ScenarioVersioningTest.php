<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ScenarioVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_versions_schema_exists_with_versioned_definition_fields(): void
    {
        $this->assertTrue(Schema::hasTable('scenario_versions'));
        $this->assertTrue(Schema::hasColumns('scenario_versions', [
            'id',
            'uuid',
            'scenario_id',
            'version_number',
            'environment',
            'threat_level',
            'mechanism',
            'estimated_casualty_count',
            'resources',
            'learning_objectives',
            'expected_actions',
            'critical_errors',
            'publication_status',
            'created_at',
            'updated_at',
        ]));
    }
}
