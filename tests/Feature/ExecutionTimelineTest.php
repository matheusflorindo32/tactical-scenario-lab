<?php

namespace Tests\Feature;

use App\Models\ExecutionEvent;
use App\Models\ExecutionParticipant;
use App\Models\ExecutionTeam;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class ExecutionTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_event_schema_exists(): void
    {
        $this->assertTrue(Schema::hasColumns('execution_events', [
            'id', 'uuid', 'scenario_execution_id', 'execution_team_id', 'execution_participant_id',
            'kind', 'occurred_at', 'summary', 'metadata', 'created_at', 'updated_at',
        ]));
    }

    public function test_running_execution_accepts_ordered_event_with_exact_timestamp_and_metadata(): void
    {
        [$organization, $execution] = $this->executionContext('running');
        $this->authenticate($organization);
        [$team, $participant] = $this->teamAndParticipant($organization, $execution);
        $occurredAt = now()->subSeconds(17)->startOfSecond();

        $this->post(route('execution-events.store', $execution), [
            'execution_team_id' => $team->id,
            'execution_participant_id' => $participant->id,
            'kind' => 'action',
            'occurred_at' => $occurredAt->toDateTimeString(),
            'summary' => 'Equipe iniciou triagem primária.',
            'metadata' => ['source' => 'instructor'],
        ])->assertRedirect();

        $event = ExecutionEvent::query()->firstOrFail();
        $this->assertSame('action', $event->kind);
        $this->assertSame('Equipe iniciou triagem primária.', $event->summary);
        $this->assertTrue($event->occurred_at->equalTo($occurredAt));
        $this->assertSame(['source' => 'instructor'], $event->metadata);
    }

    public function test_unapproved_or_sensitive_event_metadata_keys_are_rejected(): void
    {
        [$organization, $execution] = $this->executionContext('running');
        $this->authenticate($organization);

        $this->post(route('execution-events.store', $execution), [
            'kind' => 'observation',
            'occurred_at' => now()->toDateTimeString(),
            'summary' => 'Tentativa de metadata sensível.',
            'metadata' => [
                'source' => 'instructor',
                'password' => 'segredo-nao-deve-ser-persistido',
            ],
        ])->assertSessionHasErrors('metadata');

        $this->assertDatabaseCount('execution_events', 0);
    }

    public function test_timeline_writes_are_rejected_outside_running_state(): void
    {
        foreach (['draft', 'completed', 'cancelled'] as $status) {
            [$organization, $execution] = $this->executionContext($status);
            $this->authenticate($organization);

            $this->post(route('execution-events.store', $execution), [
                'kind' => 'observation',
                'occurred_at' => now()->toDateTimeString(),
                'summary' => 'Evento indevido.',
            ])->assertStatus(409);
        }

        $this->assertDatabaseCount('execution_events', 0);
    }

    public function test_event_cannot_reference_team_or_participant_from_another_execution(): void
    {
        [$organization, $execution] = $this->executionContext('running');
        [, $otherExecution] = $this->executionContext('running', $organization, 'Outro cenário timeline');
        $this->authenticate($organization);
        [$foreignTeam, $foreignParticipant] = $this->teamAndParticipant($organization, $otherExecution);

        $this->post(route('execution-events.store', $execution), [
            'execution_team_id' => $foreignTeam->id,
            'kind' => 'observation',
            'occurred_at' => now()->toDateTimeString(),
            'summary' => 'Equipe externa à execução.',
        ])->assertForbidden();

        $this->post(route('execution-events.store', $execution), [
            'execution_participant_id' => $foreignParticipant->id,
            'kind' => 'intervention',
            'occurred_at' => now()->toDateTimeString(),
            'summary' => 'Participante externo à execução.',
        ])->assertForbidden();

        $this->assertDatabaseCount('execution_events', 0);
    }

    public function test_existing_timeline_event_is_immutable_and_append_only(): void
    {
        [, $execution] = $this->executionContext('running');
        $event = ExecutionEvent::create([
            'scenario_execution_id' => $execution->id,
            'kind' => 'system',
            'occurred_at' => now(),
            'summary' => 'Execução iniciada.',
        ]);

        try {
            $event->update(['summary' => 'Conteúdo reescrito']);
            $this->fail('Expected execution event update to be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('Execution timeline events are append-only.', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Execution timeline events are append-only.');
        $event->delete();
    }

    private function executionContext(
        string $status,
        ?Organization $organization = null,
        string $title = 'Cenário Timeline',
    ): array {
        $organization ??= Organization::create([
            'name' => 'Centro Timeline M3',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Área urbana',
            'threat_level' => 'potencial',
            'casualties' => 15,
            'estimated_casualty_count' => 15,
            'mechanism' => 'Incidente simulado',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Timeline'],
            'expected_actions' => ['Registrar eventos'],
            'critical_errors' => ['Omissão de registro'],
            'status' => 'draft',
        ]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => 15,
            'resources' => ['Rádio'],
            'learning_objectives' => ['Timeline'],
            'expected_actions' => ['Registrar eventos'],
            'critical_errors' => ['Omissão de registro'],
            'publication_status' => 'published',
        ]);
        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => $status,
            'started_at' => in_array($status, ['running', 'completed'], true) ? now()->subMinute() : null,
            'completed_at' => $status === 'completed' ? now() : null,
            'cancelled_at' => $status === 'cancelled' ? now() : null,
        ]);

        return [$organization, $execution];
    }

    private function teamAndParticipant(Organization $organization, ScenarioExecution $execution): array
    {
        $person = Person::create(['display_name' => 'Participante Timeline', 'status' => 'active']);
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $team = ExecutionTeam::create([
            'scenario_execution_id' => $execution->id,
            'label' => 'Equipe Timeline '.$execution->id,
        ]);
        $participant = ExecutionParticipant::create([
            'scenario_execution_id' => $execution->id,
            'execution_team_id' => $team->id,
            'person_id' => $person->id,
            'role' => 'Operador',
        ]);

        return [$team, $participant];
    }

    private function authenticate(Organization $organization): void
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'scenario_manager',
            'abilities' => [AccessAbility::SCENARIOS_VIEW, AccessAbility::SCENARIOS_MANAGE],
            'granted_at' => now(),
        ]);
        $this->actingAs($user)->withSession(['active_organization_id' => $organization->id]);
    }
}
