<?php

namespace Tests\Feature;

use App\Models\ExecutionEvent;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ExecutionAssessmentManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentHttpMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluator_can_manage_full_draft_assessment_subresources(): void
    {
        [$organization, $execution, $assessment, $user] = $this->context();

        $this->post(route('assessment-criteria.store', $assessment), [
            'label' => 'Decisão sob pressão',
            'description' => 'Decisão proporcional ao contexto.',
            'weight' => 20,
            'score' => 85,
        ])->assertRedirect();

        $criterion = $assessment->criteria()->where('label', 'Decisão sob pressão')->firstOrFail();

        $this->patch(route('assessment-criteria.update', $criterion), [
            'label' => 'Decisão tática sob pressão',
            'description' => 'Decisão proporcional ao contexto.',
            'weight' => 20,
            'score' => 88,
        ])->assertRedirect();

        $event = ExecutionEvent::create([
            'scenario_execution_id' => $execution->id,
            'kind' => 'observation',
            'occurred_at' => $execution->started_at->copy()->addMinutes(2),
            'summary' => 'Decisão observada.',
        ]);

        $this->post(route('assessment-evidence.store', $criterion), [
            'execution_event_uuid' => $event->uuid,
            'statement' => 'A equipe avaliou risco e prioridade antes de agir.',
            'observed_at' => $event->occurred_at->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->post(route('critical-error-occurrences.store', $assessment), [
            'catalog_label_snapshot' => 'Falha de segurança',
            'rule' => 'penalty',
            'penalty_points' => 5,
            'observed_at' => $execution->started_at->copy()->addMinutes(3)->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->post(route('key-times.store', $assessment), [
            'label' => 'Primeiro comando',
            'occurred_at' => $execution->started_at->copy()->addSeconds(90)->format('Y-m-d H:i:s'),
            'elapsed_seconds' => 999999,
            'reference_seconds' => 120,
        ])->assertRedirect();

        foreach (['fact', 'interpretation', 'recommendation'] as $kind) {
            $this->post(route('debrief-entries.store', $assessment), [
                'kind' => $kind,
                'content' => ucfirst($kind).' registrado pelo avaliador.',
            ])->assertRedirect();
        }

        $this->post(route('action-items.store', $assessment), [
            'action' => 'Reforçar briefing de segurança.',
            'responsible_label' => 'Coordenação de instrução',
            'due_date' => now()->addDays(15)->toDateString(),
            'notes' => 'Revisar antes do próximo ciclo.',
        ])->assertRedirect();

        $this->assertSame('88.00', $criterion->fresh()->score);
        $this->assertCount(1, $criterion->fresh()->evidence);
        $this->assertDatabaseHas('critical_error_occurrences', [
            'execution_assessment_id' => $assessment->id,
            'catalog_label_snapshot' => 'Falha de segurança',
            'rule' => 'penalty',
        ]);
        $this->assertDatabaseHas('key_time_records', [
            'execution_assessment_id' => $assessment->id,
            'elapsed_seconds' => 90,
        ]);
        $this->assertCount(3, $assessment->fresh()->debrief->entries);
        $this->assertCount(1, $assessment->fresh()->debrief->actionItems);
        $this->assertAuthenticatedAs($user);
        $this->assertSame($organization->id, $assessment->organization_id);
    }

    public function test_foreign_execution_event_cannot_be_attached_as_evidence(): void
    {
        [$organization, $execution, $assessment] = $this->context();
        $foreignExecution = $this->execution($organization, 'Cenário paralelo');
        $foreignEvent = ExecutionEvent::create([
            'scenario_execution_id' => $foreignExecution->id,
            'kind' => 'observation',
            'occurred_at' => $foreignExecution->started_at->copy()->addMinute(),
            'summary' => 'Evento de outra execução.',
        ]);
        $criterion = $assessment->criteria()->firstOrFail();

        $this->post(route('assessment-evidence.store', $criterion), [
            'execution_event_uuid' => $foreignEvent->uuid,
            'statement' => 'Tentativa de referência cruzada.',
            'observed_at' => $execution->started_at->copy()->addMinute()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('execution_event_uuid');

        $this->assertCount(0, $criterion->fresh()->evidence);
    }

    public function test_user_without_evaluation_ability_cannot_mutate_subresources(): void
    {
        [$organization, $execution, $assessment] = $this->context();
        $this->authenticate($organization, [AccessAbility::SCENARIOS_VIEW]);
        $criterion = $assessment->criteria()->firstOrFail();

        $this->patch(route('assessment-criteria.update', $criterion), [
            'label' => 'Tentativa indevida',
            'weight' => 100,
            'score' => 10,
        ])->assertForbidden();

        $this->assertNotSame('Tentativa indevida', $criterion->fresh()->label);
        $this->assertSame($execution->organization_id, $assessment->organization_id);
    }

    private function context(): array
    {
        $organization = Organization::create([
            'name' => 'Centro M4 HTTP '.fake()->uuid(),
            'kind' => 'company',
            'status' => 'active',
        ]);
        $execution = $this->execution($organization);
        $assessment = app(ExecutionAssessmentManager::class)->createForExecution($execution);
        $user = $this->authenticate($organization, [
            AccessAbility::SCENARIOS_VIEW,
            AccessAbility::EVALUATIONS_MANAGE,
        ]);

        return [$organization, $execution, $assessment, $user];
    }

    private function authenticate(Organization $organization, array $abilities): User
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'evaluator',
            'abilities' => $abilities,
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);

        return $user;
    }

    private function execution(Organization $organization, string $title = 'Cenário HTTP M4'): ScenarioExecution
    {
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
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

        return ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subMinutes(20),
            'completed_at' => now(),
        ]);
    }
}
