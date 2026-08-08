<?php

namespace Tests\Feature;

use App\Models\AssessmentEvidence;
use App\Models\DebriefEntry;
use App\Models\ExecutionDebrief;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\User;
use App\Services\ExecutionAssessmentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AssessmentFinalizationInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_and_cancelled_executions_cannot_finalize(): void
    {
        foreach (['running', 'cancelled'] as $status) {
            [$assessment, $user] = $this->readyAssessment();
            $assessment->execution->update([
                'status' => $status,
                'completed_at' => null,
                'cancelled_at' => $status === 'cancelled' ? now() : null,
            ]);

            try {
                app(ExecutionAssessmentManager::class)->finalize($assessment->fresh(), $user);
                $this->fail('A non-completed execution was finalized.');
            } catch (LogicException) {
                $this->assertTrue($assessment->fresh()->isDraft());
            }
        }
    }

    public function test_criterion_weights_must_total_exactly_one_hundred(): void
    {
        [$assessment, $user] = $this->readyAssessment();
        $assessment->criteria()->firstOrFail()->update(['weight' => 99]);

        $this->expectException(LogicException::class);
        app(ExecutionAssessmentManager::class)->finalize($assessment->fresh(), $user);
    }

    public function test_structured_debrief_requires_all_three_semantic_categories(): void
    {
        [$assessment, $user] = $this->assessmentWithEvidence();
        $debrief = ExecutionDebrief::create(['execution_assessment_id' => $assessment->id]);

        foreach (['fact', 'interpretation'] as $position => $kind) {
            DebriefEntry::create([
                'execution_debrief_id' => $debrief->id,
                'kind' => $kind,
                'content' => ucfirst($kind).' registrado.',
                'position' => $position + 1,
                'created_by_user_id' => $user->id,
            ]);
        }

        $this->expectException(LogicException::class);
        app(ExecutionAssessmentManager::class)->finalize($assessment->fresh(), $user);
    }

    private function readyAssessment(): array
    {
        [$assessment, $user] = $this->assessmentWithEvidence();
        $debrief = ExecutionDebrief::create(['execution_assessment_id' => $assessment->id]);

        foreach (['fact', 'interpretation', 'recommendation'] as $position => $kind) {
            DebriefEntry::create([
                'execution_debrief_id' => $debrief->id,
                'kind' => $kind,
                'content' => ucfirst($kind).' registrado.',
                'position' => $position + 1,
                'created_by_user_id' => $user->id,
            ]);
        }

        return [$assessment->fresh(['execution', 'criteria']), $user];
    }

    private function assessmentWithEvidence(): array
    {
        $organization = Organization::create([
            'name' => 'Centro M4 Invariantes '.fake()->uuid(),
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário de invariantes',
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 1,
            'estimated_casualty_count' => 1,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Comando'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha de segurança'],
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
        $criterion = $assessment->criteria()->firstOrFail();
        $criterion->update(['score' => 90, 'weight' => 100]);
        $user = User::factory()->create(['status' => 'active']);

        AssessmentEvidence::create([
            'assessment_criterion_id' => $criterion->id,
            'statement' => 'Evidência objetiva.',
            'observed_at' => $execution->started_at->addMinute(),
            'created_by_user_id' => $user->id,
        ]);

        return [$assessment->fresh(['execution', 'criteria']), $user];
    }
}
