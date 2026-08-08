<?php

namespace Tests\Feature;

use App\Models\CriticalErrorOccurrence;
use App\Models\ExecutionEvent;
use App\Models\KeyTimeRecord;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Services\ExecutionAssessmentManager;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
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

    public function test_m4_critical_error_must_come_from_version_catalog(): void
    {
        $execution = $this->execution();
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);

        $occurrence = CriticalErrorOccurrence::create([
            'execution_assessment_id' => $assessment->id,
            'catalog_label_snapshot' => 'Falha de segurança',
            'rule' => 'record',
            'penalty_points' => 0,
            'observed_at' => $execution->started_at->addMinute(),
            'source' => 'm4',
        ]);

        $this->assertNotEmpty($occurrence->uuid);
        $this->assertSame('0.00', $occurrence->penalty_points);

        $this->expectException(InvalidArgumentException::class);

        CriticalErrorOccurrence::create([
            'execution_assessment_id' => $assessment->id,
            'catalog_label_snapshot' => 'Erro inventado fora do catálogo',
            'rule' => 'record',
            'penalty_points' => 0,
            'observed_at' => $execution->started_at->addMinutes(2),
            'source' => 'm4',
        ]);
    }

    public function test_penalty_and_automatic_fail_rules_have_explicit_numeric_semantics(): void
    {
        $execution = $this->execution();
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);

        $penalty = CriticalErrorOccurrence::create([
            'execution_assessment_id' => $assessment->id,
            'catalog_label_snapshot' => 'Falha de segurança',
            'rule' => 'penalty',
            'penalty_points' => 12.5,
            'observed_at' => $execution->started_at->addMinute(),
            'source' => 'm4',
        ]);

        $automaticFail = CriticalErrorOccurrence::create([
            'execution_assessment_id' => $assessment->id,
            'catalog_label_snapshot' => 'Abandono de comando',
            'rule' => 'automatic_fail',
            'penalty_points' => 0,
            'observed_at' => $execution->started_at->addMinutes(2),
            'source' => 'm4',
        ]);

        $this->assertSame('12.50', $penalty->penalty_points);
        $this->assertSame('0.00', $automaticFail->penalty_points);
    }

    public function test_penalty_rule_rejects_zero_or_excessive_points(): void
    {
        $execution = $this->execution();
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);

        foreach ([0, 100.01] as $points) {
            try {
                CriticalErrorOccurrence::create([
                    'execution_assessment_id' => $assessment->id,
                    'catalog_label_snapshot' => 'Falha de segurança',
                    'rule' => 'penalty',
                    'penalty_points' => $points,
                    'observed_at' => $execution->started_at->addMinute(),
                    'source' => 'm4',
                ]);

                $this->fail('Invalid penalty points were accepted.');
            } catch (InvalidArgumentException) {
                $this->assertDatabaseMissing('critical_error_occurrences', [
                    'execution_assessment_id' => $assessment->id,
                ]);
            }
        }
    }

    public function test_same_catalog_error_cannot_be_recorded_twice_in_one_assessment(): void
    {
        $execution = $this->execution();
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);
        $payload = [
            'execution_assessment_id' => $assessment->id,
            'catalog_label_snapshot' => 'Falha de segurança',
            'rule' => 'record',
            'penalty_points' => 0,
            'observed_at' => $execution->started_at->addMinute(),
            'source' => 'm4',
        ];

        CriticalErrorOccurrence::create($payload);

        $this->expectException(QueryException::class);
        CriticalErrorOccurrence::create($payload);
    }

    public function test_critical_error_event_must_belong_to_same_execution(): void
    {
        $execution = $this->execution();
        $foreignExecution = $this->execution('Cenário estrangeiro');
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);
        $foreignEvent = ExecutionEvent::create([
            'scenario_execution_id' => $foreignExecution->id,
            'kind' => 'observation',
            'occurred_at' => $foreignExecution->started_at->addMinute(),
            'summary' => 'Evento estrangeiro.',
        ]);

        $this->expectException(InvalidArgumentException::class);

        CriticalErrorOccurrence::create([
            'execution_assessment_id' => $assessment->id,
            'catalog_label_snapshot' => 'Falha de segurança',
            'rule' => 'record',
            'penalty_points' => 0,
            'execution_event_id' => $foreignEvent->id,
            'observed_at' => $execution->started_at->addMinute(),
            'source' => 'm4',
        ]);
    }

    public function test_key_time_derives_elapsed_seconds_on_server_and_ignores_forged_value(): void
    {
        $startedAt = Carbon::parse('2026-08-08 12:00:00');
        $execution = $this->execution(startedAt: $startedAt);
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);

        $keyTime = KeyTimeRecord::create([
            'execution_assessment_id' => $assessment->id,
            'label' => 'Primeiro contato',
            'occurred_at' => $startedAt->copy()->addSeconds(95),
            'elapsed_seconds' => 999999,
            'reference_seconds' => 120,
            'notes' => 'Tempo observado.',
        ]);

        $this->assertSame(95, $keyTime->elapsed_seconds);
        $this->assertNotEmpty($keyTime->uuid);
    }

    public function test_key_time_cannot_fall_outside_execution_window(): void
    {
        $startedAt = Carbon::parse('2026-08-08 12:00:00');
        $completedAt = $startedAt->copy()->addMinutes(10);
        $execution = $this->execution(startedAt: $startedAt, completedAt: $completedAt);
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);

        foreach ([$startedAt->copy()->subSecond(), $completedAt->copy()->addSecond()] as $occurredAt) {
            try {
                KeyTimeRecord::create([
                    'execution_assessment_id' => $assessment->id,
                    'label' => 'Tempo inválido',
                    'occurred_at' => $occurredAt,
                ]);

                $this->fail('Out-of-window key time was accepted.');
            } catch (InvalidArgumentException) {
                $this->assertDatabaseMissing('key_time_records', [
                    'execution_assessment_id' => $assessment->id,
                ]);
            }
        }
    }

    private function execution(
        string $title = 'Cenário observacional',
        ?Carbon $startedAt = null,
        ?Carbon $completedAt = null,
    ): ScenarioExecution {
        $organization = Organization::firstOrCreate(
            ['name' => 'Centro M4 Observação'],
            ['kind' => 'company', 'status' => 'active'],
        );

        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 2,
            'estimated_casualty_count' => 2,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Comunicação'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha de segurança', 'Abandono de comando'],
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

        $startedAt ??= now()->subMinutes(10);

        return ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => $completedAt ? 'completed' : 'running',
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);
    }
}
