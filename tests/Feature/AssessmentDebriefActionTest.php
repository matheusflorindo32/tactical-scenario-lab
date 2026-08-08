<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\DebriefEntry;
use App\Models\ExecutionDebrief;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\User;
use App\Services\ExecutionAssessmentManager;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
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
        $this->assertTrue(Schema::hasTable('action_items'));
    }

    public function test_assessment_has_only_one_debrief_and_m4_debrief_is_structured(): void
    {
        [$assessment] = $this->assessment();
        $user = User::factory()->create(['status' => 'active']);
        $debrief = ExecutionDebrief::create(['execution_assessment_id' => $assessment->id]);

        foreach (['fact', 'interpretation', 'recommendation'] as $position => $kind) {
            $entry = DebriefEntry::create([
                'execution_debrief_id' => $debrief->id,
                'kind' => $kind,
                'content' => ucfirst($kind).' registrado.',
                'position' => $position + 1,
                'created_by_user_id' => $user->id,
            ]);
            $this->assertNotEmpty($entry->uuid);
        }

        try {
            DebriefEntry::create([
                'execution_debrief_id' => $debrief->id,
                'kind' => 'legacy_unstructured',
                'content' => 'Texto legado indevido em assessment M4.',
                'created_by_user_id' => $user->id,
            ]);
            $this->fail('legacy_unstructured was accepted for a normal M4 assessment.');
        } catch (InvalidArgumentException) {
            $this->assertCount(3, $debrief->entries()->get());
        }

        $this->expectException(QueryException::class);
        ExecutionDebrief::create(['execution_assessment_id' => $assessment->id]);
    }

    public function test_action_item_requires_responsible_party_and_deadline(): void
    {
        [$assessment] = $this->assessment();
        $debrief = ExecutionDebrief::create(['execution_assessment_id' => $assessment->id]);

        foreach ([
            ['action' => 'Reforçar comunicação.', 'due_date' => now()->addWeek()->toDateString()],
            ['action' => 'Reforçar comunicação.', 'responsible_label' => 'Equipe Alfa'],
        ] as $payload) {
            try {
                ActionItem::create(['execution_debrief_id' => $debrief->id, ...$payload]);
                $this->fail('Incomplete action item was accepted.');
            } catch (InvalidArgumentException) {
                $this->assertDatabaseCount('action_items', 0);
            }
        }
    }

    public function test_responsible_person_must_have_active_membership_in_assessment_organization(): void
    {
        [$assessment, $organization] = $this->assessment();
        $debrief = ExecutionDebrief::create(['execution_assessment_id' => $assessment->id]);
        $person = Person::create(['display_name' => 'Responsável Externo', 'status' => 'active']);

        try {
            ActionItem::create([
                'execution_debrief_id' => $debrief->id,
                'action' => 'Treinar comunicação.',
                'responsible_person_id' => $person->id,
                'due_date' => now()->addWeek()->toDateString(),
            ]);
            $this->fail('Person without active membership was accepted.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('action_items', 0);
        }

        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'position' => 'Instrutor',
            'started_at' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        $action = ActionItem::create([
            'execution_debrief_id' => $debrief->id,
            'action' => 'Treinar comunicação.',
            'responsible_person_id' => $person->id,
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $this->assertSame('open', $action->status);
        $this->assertNotEmpty($action->uuid);
    }

    public function test_action_status_follows_explicit_transition_matrix(): void
    {
        [$assessment] = $this->assessment();
        $debrief = ExecutionDebrief::create(['execution_assessment_id' => $assessment->id]);
        $actor = User::factory()->create(['status' => 'active']);
        $action = ActionItem::create([
            'execution_debrief_id' => $debrief->id,
            'action' => 'Revisar protocolo.',
            'responsible_label' => 'Coordenação',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $action->transitionTo('in_progress', $actor);
        $this->assertSame('in_progress', $action->fresh()->status);
        $this->assertSame($actor->id, $action->fresh()->status_changed_by_user_id);
        $this->assertNotNull($action->fresh()->status_changed_at);

        $action->fresh()->transitionTo('completed', $actor);
        $this->assertSame('completed', $action->fresh()->status);

        $this->expectException(LogicException::class);
        $action->fresh()->transitionTo('open', $actor);
    }

    public function test_finalized_assessment_freezes_action_content_but_allows_status_follow_up(): void
    {
        [$assessment] = $this->assessment();
        $debrief = ExecutionDebrief::create(['execution_assessment_id' => $assessment->id]);
        $actor = User::factory()->create(['status' => 'active']);
        $action = ActionItem::create([
            'execution_debrief_id' => $debrief->id,
            'action' => 'Ação histórica.',
            'responsible_label' => 'Coordenação',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $assessment->update(['status' => 'finalized']);

        try {
            $action->update(['action' => 'Conteúdo reescrito.']);
            $this->fail('Finalized action content was mutated.');
        } catch (LogicException) {
            $this->assertSame('Ação histórica.', $action->fresh()->action);
        }

        $action->fresh()->transitionTo('completed', $actor);
        $this->assertSame('completed', $action->fresh()->status);
    }

    private function assessment(): array
    {
        $organization = Organization::create([
            'name' => 'Centro M4 Debrief '.fake()->uuid(),
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário de debrief',
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 1,
            'estimated_casualty_count' => 1,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Comunicação'],
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

        return [$assessment, $organization, $execution];
    }
}
