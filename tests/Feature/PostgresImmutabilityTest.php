<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PostgresRuntimeRole;
use Tests\TestCase;

class PostgresImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL-specific database immutability test.');
        }

        (new DemoSeeder)->run();
    }

    public function test_runtime_sql_cannot_change_published_scenario_definition(): void
    {
        $versionId = DB::table('scenario_versions')->where('publication_status', 'published')->value('id');
        $this->assertNotNull($versionId);

        PostgresRuntimeRole::activateWithinTransaction(DB::connection());
        $this->expectException(QueryException::class);

        DB::table('scenario_versions')->where('id', $versionId)->update([
            'environment' => 'Forbidden direct runtime mutation',
            'updated_at' => now(),
        ]);
    }

    public function test_runtime_sql_cannot_change_finalized_assessment_row(): void
    {
        $assessmentId = $this->finalizedAssessmentId();

        PostgresRuntimeRole::activateWithinTransaction(DB::connection());
        $this->expectException(QueryException::class);

        DB::table('execution_assessments')->where('id', $assessmentId)->update([
            'evaluator_adjustment' => 99,
            'updated_at' => now(),
        ]);
    }

    #[DataProvider('finalizedContentMutations')]
    public function test_runtime_sql_cannot_change_finalized_assessment_content(
        string $table,
        string $column,
        string $value,
    ): void {
        $rowId = $this->historicalContentRowId($table, $this->finalizedAssessmentId());
        $this->assertNotNull($rowId, "Demo graph must contain {$table} for the finalized assessment.");

        PostgresRuntimeRole::activateWithinTransaction(DB::connection());
        $this->expectException(QueryException::class);

        DB::table($table)->where('id', $rowId)->update([
            $column => $value,
            'updated_at' => now(),
        ]);
    }

    public function test_runtime_sql_cannot_update_execution_timeline_event(): void
    {
        $eventId = DB::table('execution_events')->value('id');
        $this->assertNotNull($eventId);

        PostgresRuntimeRole::activateWithinTransaction(DB::connection());
        $this->expectException(QueryException::class);

        DB::table('execution_events')->where('id', $eventId)->update([
            'summary' => 'Forbidden rewritten timeline event',
            'updated_at' => now(),
        ]);
    }

    public function test_runtime_sql_cannot_delete_execution_timeline_event(): void
    {
        $eventId = DB::table('execution_events')->value('id');
        $this->assertNotNull($eventId);

        PostgresRuntimeRole::activateWithinTransaction(DB::connection());
        $this->expectException(QueryException::class);

        DB::table('execution_events')->where('id', $eventId)->delete();
    }

    public function test_runtime_sql_can_advance_action_status_after_assessment_finalization(): void
    {
        $assessmentId = $this->finalizedAssessmentId();
        $actionId = $this->historicalContentRowId('action_items', $assessmentId);
        $this->assertNotNull($actionId);

        PostgresRuntimeRole::activateWithinTransaction(DB::connection());

        $updated = DB::table('action_items')->where('id', $actionId)->update([
            'status' => 'in_progress',
            'status_changed_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(1, $updated);
        $this->assertSame('in_progress', DB::table('action_items')->where('id', $actionId)->value('status'));
    }

    public static function finalizedContentMutations(): array
    {
        return [
            'criterion' => ['assessment_criteria', 'label', 'Forbidden criterion rewrite'],
            'evidence' => ['assessment_evidence', 'statement', 'Forbidden evidence rewrite'],
            'critical error' => ['critical_error_occurrences', 'label', 'Forbidden critical error rewrite'],
            'key time' => ['key_time_records', 'name', 'Forbidden key time rewrite'],
            'debrief' => ['execution_debriefs', 'summary', 'Forbidden debrief rewrite'],
            'debrief note' => ['debrief_notes', 'content', 'Forbidden debrief note rewrite'],
            'action content' => ['action_items', 'action', 'Forbidden action rewrite'],
        ];
    }

    private function finalizedAssessmentId(): int
    {
        $id = DB::table('execution_assessments')->where('status', 'finalized')->value('id');
        $this->assertNotNull($id);

        return (int) $id;
    }

    private function historicalContentRowId(string $table, int $assessmentId): ?int
    {
        $id = match ($table) {
            'assessment_criteria' => DB::table('assessment_criteria')
                ->where('execution_assessment_id', $assessmentId)
                ->value('id'),
            'assessment_evidence' => DB::table('assessment_evidence as evidence')
                ->join('assessment_criteria as criteria', 'criteria.id', '=', 'evidence.assessment_criterion_id')
                ->where('criteria.execution_assessment_id', $assessmentId)
                ->value('evidence.id'),
            'critical_error_occurrences' => DB::table('critical_error_occurrences')
                ->where('execution_assessment_id', $assessmentId)
                ->value('id'),
            'key_time_records' => DB::table('key_time_records')
                ->where('execution_assessment_id', $assessmentId)
                ->value('id'),
            'execution_debriefs' => DB::table('execution_debriefs')
                ->where('execution_assessment_id', $assessmentId)
                ->value('id'),
            'debrief_notes' => DB::table('debrief_notes as notes')
                ->join('execution_debriefs as debriefs', 'debriefs.id', '=', 'notes.execution_debrief_id')
                ->where('debriefs.execution_assessment_id', $assessmentId)
                ->value('notes.id'),
            'action_items' => DB::table('action_items as actions')
                ->join('execution_debriefs as debriefs', 'debriefs.id', '=', 'actions.execution_debrief_id')
                ->where('debriefs.execution_assessment_id', $assessmentId)
                ->where('actions.status', 'open')
                ->value('actions.id'),
            default => null,
        };

        return $id === null ? null : (int) $id;
    }
}
