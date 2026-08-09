<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function test_runtime_sql_can_change_draft_scenario_definition(): void
    {
        $versionId = $this->createDraftScenarioVersion();

        PostgresRuntimeRole::activateWithinTransaction(DB::connection());

        $updated = DB::table('scenario_versions')->where('id', $versionId)->update([
            'environment' => 'Valid draft runtime mutation',
            'updated_at' => now(),
        ]);

        $this->assertSame(1, $updated);
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

    public function test_runtime_sql_cannot_delete_finalized_assessment_row(): void
    {
        $assessmentId = $this->finalizedAssessmentId();

        PostgresRuntimeRole::activateWithinTransaction(DB::connection());
        $this->expectException(QueryException::class);

        DB::table('execution_assessments')->where('id', $assessmentId)->delete();
    }

    public function test_runtime_sql_can_change_draft_assessment_content(): void
    {
        $assessmentId = DB::table('execution_assessments')->where('status', 'draft')->value('id');
        $this->assertNotNull($assessmentId);

        $criterionId = DB::table('assessment_criteria')
            ->where('execution_assessment_id', $assessmentId)
            ->value('id');

        if ($criterionId === null) {
            $criterionId = DB::table('assessment_criteria')->insertGetId([
                'uuid' => fake()->uuid(),
                'execution_assessment_id' => $assessmentId,
                'label' => 'Draft criterion',
                'weight' => 100,
                'max_score' => 10,
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        PostgresRuntimeRole::activateWithinTransaction(DB::connection());

        $updated = DB::table('assessment_criteria')->where('id', $criterionId)->update([
            'label' => 'Valid draft criterion mutation',
            'updated_at' => now(),
        ]);

        $this->assertSame(1, $updated);
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

    #[DataProvider('finalizedContentTables')]
    public function test_runtime_sql_cannot_delete_finalized_assessment_content(string $table): void
    {
        $rowId = $this->historicalContentRowId($table, $this->finalizedAssessmentId());
        $this->assertNotNull($rowId, "Demo graph must contain {$table} for the finalized assessment.");

        PostgresRuntimeRole::activateWithinTransaction(DB::connection());
        $this->expectException(QueryException::class);

        DB::table($table)->where('id', $rowId)->delete();
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
            'critical error' => ['critical_error_occurrences', 'catalog_label_snapshot', 'Forbidden critical error rewrite'],
            'key time' => ['key_time_records', 'label', 'Forbidden key time rewrite'],
            'debrief entry' => ['debrief_entries', 'content', 'Forbidden debrief entry rewrite'],
            'action content' => ['action_items', 'action', 'Forbidden action rewrite'],
        ];
    }

    public static function finalizedContentTables(): array
    {
        return [
            'criterion' => ['assessment_criteria'],
            'evidence' => ['assessment_evidence'],
            'critical error' => ['critical_error_occurrences'],
            'key time' => ['key_time_records'],
            'debrief entry' => ['debrief_entries'],
            'action content' => ['action_items'],
        ];
    }

    private function createDraftScenarioVersion(): int
    {
        $published = DB::table('scenario_versions')
            ->where('publication_status', 'published')
            ->orderBy('id')
            ->first();
        $this->assertNotNull($published);

        $nextVersion = ((int) DB::table('scenario_versions')
            ->where('scenario_id', $published->scenario_id)
            ->max('version_number')) + 1;

        return (int) DB::table('scenario_versions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'scenario_id' => $published->scenario_id,
            'version_number' => $nextVersion,
            'environment' => $published->environment,
            'threat_level' => $published->threat_level,
            'mechanism' => $published->mechanism,
            'estimated_casualty_count' => $published->estimated_casualty_count,
            'resources' => $published->resources,
            'learning_objectives' => $published->learning_objectives,
            'expected_actions' => $published->expected_actions,
            'critical_errors' => $published->critical_errors,
            'publication_status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
            'debrief_entries' => DB::table('debrief_entries as entries')
                ->join('execution_debriefs as debriefs', 'debriefs.id', '=', 'entries.execution_debrief_id')
                ->where('debriefs.execution_assessment_id', $assessmentId)
                ->value('entries.id'),
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
