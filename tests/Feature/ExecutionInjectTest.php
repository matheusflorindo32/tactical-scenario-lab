<?php

namespace Tests\Feature;

use App\Models\ExecutionInject;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ExecutionInjectManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class ExecutionInjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_inject_schema_exists(): void
    {
        $this->assertTrue(Schema::hasColumns('execution_injects', [
            'id', 'uuid', 'scenario_execution_id', 'label', 'content', 'planned_offset_seconds',
            'status', 'delivered_at', 'cancelled_at', 'created_at', 'updated_at',
        ]));
    }

    public function test_planned_inject_can_be_created_for_draft_or_running_execution(): void
    {
        [$organization, $draft] = $this->executionContext('draft');
        [, $running] = $this->executionContext('running', $organization, 'Execução ativa para inject');
        $this->authenticate($organization);

        foreach ([$draft, $running] as $execution) {
            $this->post(route('execution-injects.store', $execution), [
                'label' => 'Mudança de contexto '.$execution->id,
                'content' => 'Apresente nova informação operacional à equipe.',
                'planned_offset_seconds' => 120,
            ])->assertRedirect();
        }

        $this->assertDatabaseCount('execution_injects', 2);
        $this->assertSame(['planned', 'planned'], ExecutionInject::query()->orderBy('id')->pluck('status')->all());
    }

    public function test_delivering_planned_inject_marks_it_once_and_appends_exactly_one_timeline_event(): void
    {
        [, $execution] = $this->executionContext('running');
        $inject = ExecutionInject::create([
            'scenario_execution_id' => $execution->id,
            'label' => 'Interrupção de comunicação',
            'content' => 'Simular indisponibilidade temporária do rádio principal.',
            'status' => 'planned',
        ]);
        $manager = app(ExecutionInjectManager::class);

        $manager->deliver($inject);
        $inject->refresh();

        $this->assertSame('delivered', $inject->status);
        $this->assertNotNull($inject->delivered_at);
        $this->assertDatabaseCount('execution_events', 1);
        $this->assertDatabaseHas('execution_events', [
            'scenario_execution_id' => $execution->id,
            'kind' => 'inject',
            'summary' => 'Inject entregue: Interrupção de comunicação',
        ]);

        try {
            $manager->deliver($inject->fresh());
            $this->fail('Expected already delivered inject to be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('Only planned injects can be delivered.', $exception->getMessage());
        }

        $this->assertDatabaseCount('execution_events', 1);
    }

    public function test_inject_delivery_requires_running_execution(): void
    {
        foreach (['draft', 'completed', 'cancelled'] as $status) {
            [, $execution] = $this->executionContext($status);
            $inject = ExecutionInject::create([
                'scenario_execution_id' => $execution->id,
                'label' => 'Inject '.$status,
                'content' => 'Conteúdo de teste.',
                'status' => 'planned',
            ]);

            try {
                app(ExecutionInjectManager::class)->deliver($inject);
                $this->fail('Expected inject delivery outside running execution to be rejected.');
            } catch (LogicException $exception) {
                $this->assertSame('Injects can only be delivered while the execution is running.', $exception->getMessage());
            }
        }

        $this->assertDatabaseCount('execution_events', 0);
    }

    public function test_cancelled_inject_cannot_be_delivered(): void
    {
        [, $execution] = $this->executionContext('running');
        $inject = ExecutionInject::create([
            'scenario_execution_id' => $execution->id,
            'label' => 'Inject cancelável',
            'content' => 'Conteúdo de teste.',
            'status' => 'planned',
        ]);
        $manager = app(ExecutionInjectManager::class);

        $manager->cancel($inject);
        $this->assertSame('cancelled', $inject->fresh()->status);
        $this->assertNotNull($inject->fresh()->cancelled_at);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Only planned injects can be delivered.');
        $manager->deliver($inject->fresh());
    }

    private function executionContext(
        string $status,
        ?Organization $organization = null,
        string $title = 'Cenário Injects',
    ): array {
        $organization ??= Organization::create([
            'name' => 'Centro Injects M3',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Área urbana',
            'threat_level' => 'potencial',
            'casualties' => 12,
            'estimated_casualty_count' => 12,
            'mechanism' => 'Incidente simulado',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Adaptabilidade'],
            'expected_actions' => ['Reavaliar contexto'],
            'critical_errors' => ['Ignorar nova informação'],
            'status' => 'draft',
        ]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => 12,
            'resources' => ['Rádio'],
            'learning_objectives' => ['Adaptabilidade'],
            'expected_actions' => ['Reavaliar contexto'],
            'critical_errors' => ['Ignorar nova informação'],
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
