<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ScenarioVictimModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_victim_and_cohort_storage_contracts_exist(): void
    {
        $this->assertTrue(Schema::hasTable('scenario_victims'));
        $this->assertTrue(Schema::hasColumns('scenario_victims', [
            'id',
            'uuid',
            'scenario_version_id',
            'code',
            'profile',
            'injuries',
            'initial_state',
            'expected_priority',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('victim_cohorts'));
        $this->assertTrue(Schema::hasColumns('victim_cohorts', [
            'id',
            'uuid',
            'scenario_version_id',
            'label',
            'quantity',
            'profile',
            'triage_category',
            'characteristics',
            'created_at',
            'updated_at',
        ]));
    }
}
