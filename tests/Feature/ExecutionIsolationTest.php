<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutionIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_publish_active_organization_version_and_create_execution(): void
    {
        [$activeOrganization] = $this->organizations();
        $this->authenticate($activeOrganization);
        [, $version] = $this->scenarioVersion($activeOrganization, 'draft');

        $this->patch(route('scenario-versions.publish', $version))
            ->assertRedirect();

        $this->assertSame('published', $version->refresh()->publication_status);

        $this->post(route('executions.store', $version))
            ->assertRedirect();

        $execution = ScenarioExecution::query()->firstOrFail();

        $this->assertSame($activeOrganization->id, $execution->organization_id);
        $this->assertSame($version->id, $execution->scenario_version_id);
        $this->assertSame(1, $execution->sequence_number);
        $this->assertSame('draft', $execution->status);
    }

    public function test_cross_organization_version_cannot_be_published_or_executed(): void
    {
        [$activeOrganization, $externalOrganization] = $this->organizations();
        $this->authenticate($activeOrganization);
        [, $externalDraft] = $this->scenarioVersion($externalOrganization, 'draft');
        [, $externalPublished] = $this->scenarioVersion($externalOrganization, 'published', 'Cenário externo publicado');

        $this->patch(route('scenario-versions.publish', $externalDraft))
            ->assertForbidden();
        $this->assertSame('draft', $externalDraft->refresh()->publication_status);

        $this->post(route('executions.store', $externalPublished))
            ->assertForbidden();
        $this->assertDatabaseCount('scenario_executions', 0);
    }

    public function test_cross_organization_execution_read_and_lifecycle_writes_are_forbidden_without_mutation(): void
    {
        [$activeOrganization, $externalOrganization] = $this->organizations();
        $this->authenticate($activeOrganization);
        [, $externalVersion] = $this->scenarioVersion($externalOrganization, 'published');

        $execution = ScenarioExecution::create([
            'organization_id' => $externalOrganization->id,
            'scenario_version_id' => $externalVersion->id,
            'sequence_number' => 1,
            'status' => 'draft',
        ]);

        $this->get(route('executions.show', $execution))->assertForbidden();
        $this->patch(route('executions.start', $execution))->assertForbidden();
        $this->assertSame('draft', $execution->refresh()->status);
        $this->assertNull($execution->started_at);

        $execution->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->patch(route('executions.complete', $execution))->assertForbidden();
        $this->patch(route('executions.cancel', $execution))->assertForbidden();

        $execution->refresh();
        $this->assertSame('running', $execution->status);
        $this->assertNull($execution->completed_at);
        $this->assertNull($execution->cancelled_at);
    }

    public function test_view_only_access_can_open_execution_but_cannot_mutate_lifecycle(): void
    {
        [$organization] = $this->organizations();
        $this->authenticate($organization, [AccessAbility::SCENARIOS_VIEW]);
        [, $version] = $this->scenarioVersion($organization, 'published');
        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'draft',
        ]);

        $this->get(route('executions.show', $execution))->assertOk();
        $this->patch(route('executions.start', $execution))->assertForbidden();

        $this->assertSame('draft', $execution->refresh()->status);
    }

    private function authenticate(Organization $organization, ?array $abilities = null): void
    {
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'scenario_manager',
            'abilities' => $abilities ?? [AccessAbility::SCENARIOS_VIEW, AccessAbility::SCENARIOS_MANAGE],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);
    }

    private function organizations(): array
    {
        return [
            Organization::create([
                'name' => 'Organização Ativa M3',
                'kind' => 'company',
                'status' => 'active',
            ]),
            Organization::create([
                'name' => 'Organização Externa M3',
                'kind' => 'company',
                'status' => 'active',
            ]),
        ];
    }

    private function scenarioVersion(
        Organization $organization,
        string $publicationStatus,
        string $title = 'Cenário de execução M3',
    ): array {
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Ambiente urbano',
            'threat_level' => 'potencial',
            'casualties' => 25,
            'estimated_casualty_count' => 25,
            'mechanism' => 'Colisão com múltiplas vítimas',
            'resources' => ['Kit IFAK'],
            'learning_objectives' => ['Validar execução institucional'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha de segurança de cena'],
            'status' => 'draft',
        ]);

        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => $scenario->estimated_casualty_count,
            'resources' => $scenario->resources,
            'learning_objectives' => $scenario->learning_objectives,
            'expected_actions' => $scenario->expected_actions,
            'critical_errors' => $scenario->critical_errors,
            'publication_status' => $publicationStatus,
        ]);

        return [$scenario, $version];
    }
}
