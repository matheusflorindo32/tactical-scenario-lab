<?php

namespace Tests\Feature;

use App\Models\AssessmentEvidence;
use App\Models\CriticalErrorOccurrence;
use App\Models\DebriefEntry;
use App\Models\ExecutionDebrief;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\User;
use App\Services\ExecutionAssessmentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class AssessmentFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_assessment_finalizes_with_hybrid_scoring_snapshot(): void
    {
        [$assessment, $user] = $this->readyAssessment([80, 90], [40, 60]);
        CriticalErrorOccurrence::create([
            'execution_assessment_id' => $assessment->id,
            'catalog_label_snapshot' => 'Falha de segurança',
            'rule' => 'penalty',
            'penalty_points' => 7,
            'observed_at' => $assessment->execution->started_at->addMinute(),
            'source' => 'm4',
        ]);

        $manager = app(ExecutionAssessmentManager::class);
        $manager->setAdjustment($assessment, 2, 'Julgamento profissional fundamentado.');
        $final = $manager->finalize($assessment->fresh(), $user);

        $this->assertTrue($final->isFinalized());
        $this->assertSame('86.00', $final->base_score);
        $this->assertSame('7.00', $final->penalty_points);
        $this->assertSame(2, $final->evaluator_adjustment);
        $this->assertSame('81.00', $final->final_score);
        $this->assertSame('passed', $final->result);
        $this->assertFalse($final->automatic_fail);
        $this->assertSame($user->id, $final->finalized_by_user_id);
        $this->assertNotNull($final->finalized_at);
    }

    public function test_automatic_fail_overrides_pass_result_without_rewriting_numerical_score(): void
    {
        [$assessment, $user] = $this->readyAssessment([95], [100]);
        CriticalErrorOccurrence::create([
            'execution_assessment_id' => $assessment->id,
            'catalog_label_snapshot' => 'Abandono de comando',
            'rule' => 'automatic_fail',
            'penalty_points' => 0,
            'observed_at' => $assessment->execution->started_at->addMinutes(2),
            'source' => 'm4',
        ]);

        $final = app(ExecutionAssessmentManager::class)->finalize($assessment, $user);

        $this->assertSame('95.00', $final->final_score);
        $this->assertTrue($final->automatic_fail);
        $this->assertSame('failed', $final->result);
    }

    public function test_finalization_requires_complete_rubric_evidence_and_structured_debrief(): void
    {
        [$assessment, $user] = $this->assessmentWithCriteria([100], [100]);

        $this->expectException(LogicException::class);
        app(ExecutionAssessmentManager::class)->finalize($assessment, $user);
    }

    public function test_nonzero_adjustment_requires_justification_and_range_is_bounded(): void
    {
        [$assessment] = $this->assessmentWithCriteria([100], [100]);
        $manager = app(ExecutionAssessmentManager::class);

        foreach ([[11, 'Excesso'], [-11, 'Excesso'], [2, null]] as [$value, $justification]) {
            try {
                $manager->setAdjustment($assessment->fresh(), $value, $justification);
                $this->fail('Invalid evaluator adjustment was accepted.');
            } catch (InvalidArgumentException) {
                $this->assertSame(0, $assessment->fresh()->evaluator_adjustment);
            }
        }
    }

    public function test_finalization_rechecks_persisted_state_and_rejects_stale_second_finalize(): void
    {
        [$assessment, $user] = $this->readyAssessment([100], [100]);
        $stale = $assessment->fresh();
        $manager = app(ExecutionAssessmentManager::class);

        $first = $manager->finalize($assessment, $user);
        $this->assertTrue($first->isFinalized());

        $this->expectException(LogicException::class);
        $manager->finalize($stale, $user);
    }

    public function test_finalized_assessment_criterion_content_is_immutable(): void
    {
        [$assessment, $user] = $this->readyAssessment([100], [100]);
        $manager = app(ExecutionAssessmentManager::class);
        $manager->finalize($assessment, $user);
        $criterion = $assessment->criteria()->firstOrFail();

        $this->expectException(LogicException::class);
        $criterion->update(['score' => 50]);
    }

    private function readyAssessment(array $scores, array $weights): array
    {
        [$assessment, $user] = $this->assessmentWithCriteria($scores, $weights);

        foreach ($assessment->criteria as $criterion) {
            AssessmentEvidence::create([
                'assessment_criterion_id' => $criterion->id,
                'statement' => 'Evidência objetiva do critério '.$criterion->position.'.',
                'observed_at' => $assessment->execution->started_at->addMinutes($criterion->position),
                'created_by_user_id' => $user->id,
            ]);
        }

        $debrief = ExecutionDebrief::create(['execution_assessment_id' => $assessment->id]);
        foreach (['fact', 'interpretation', 'recommendation'] as $position => $kind) {
            DebriefEntry::create([
                'execution_debrief_id' => $debrief->id,
                'kind' => $kind,
                'content' => ucfirst($kind).' estruturado.',
                'position' => $position + 1,
                'created_by_user_id' => $user->id,
            ]);
        }

        return [$assessment->fresh(['execution', 'criteria']), $user];
    }

    private function assessmentWithCriteria(array $scores, array $weights): array
    {
        $organization = Organization::create([
            'name' => 'Centro M4 Finalização '.fake()->uuid(),
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário de finalização',
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 1,
            'estimated_casualty_count' => 1,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => array_map(fn (int $index): string => 'Critério '.($index + 1), array_keys($scores)),
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
        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subMinutes(20),
            'completed_at' => now(),
        ]);
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);

        foreach ($assessment->criteria as $index => $criterion) {
            $criterion->update([
                'score' => $scores[$index],
                'weight' => $weights[$index],
            ]);
        }

        $user = User::factory()->create(['status' => 'active']);

        return [$assessment->fresh(['execution', 'criteria']), $user];
    }
}
