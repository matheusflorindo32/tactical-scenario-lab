<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssessmentDebriefActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_debrief_and_action_plan_schema_exists(): void
    {
        $this->assertTrue(Schema::hasTable('execution_debriefs'));
        $this->assertTrue(Schema::hasColumns('execution_debriefs', [
            'id', 'uuid', 'execution_assessment_id', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('debrief_entries'));
        $this->assertTrue(Schema::hasColumns('debrief_entries', [
            'id', 'uuid', 'execution_debrief_id', 'kind', 'content', 'position',
            'created_by_user_id', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('action_items'));
        $this->assertTrue(Schema::hasColumns('action_items', [
            'id', 'uuid', 'execution_debrief_id', 'action', 'responsible_person_id',
            'responsible_label', 'due_date', 'status', 'notes', 'status_changed_at',
            'status_changed_by_user_id', 'created_at', 'updated_at',
        ]));
    }
}
