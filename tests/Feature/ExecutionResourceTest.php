<?php

namespace Tests\Feature;

use App\Models\ExecutionResource;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ScenarioExecutionManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class ExecutionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_resource_schema_exists(): void
    {
        $this->assertTrue(Schema::hasColumns('execution_resources', [
            'id', 'uuid', 'scenario_execution_id', 'name', 'planned_quantity',
            'available_quantity', 'used_quantity', 'status', 'created_at', 'updated_at',
        ]));
    }

    public function test_execution_creation_snapshots_resources_from_published_version(): void
    {
        [, $version] = $this->publishedVersion(['Kit IFAK', 'Rádio']);

        $execution = app(ScenarioExecutionManager::class)->create($version);

        $this->assertCount(2, $execution->fresh()->resources);
        $this->assertSame(
            ['Kit IFAK', 'Rádio'],
            $execution->fresh()->resources->pluck('name')->sort()->values()->all(),
        );
        $this->assertTrue($execution->fresh()->resources->every(
            fn (ExecutionResource $resource): bool => $resource->planned_quantity === 1
                && $resource->available_quantity === 1
                && $resource->used_quantity === 0
                && $resource->status === 'available',
        ));
    }

    public function test_resource_quantity_invariant_is_enforced_at_domain_boundary(): void
    {
        [, $version] = $this->publishedVersion(['Rádio']);
        $execution = app(ScenarioExecutionManager::class)->create($version);
        $resource = $execution->resources()->firstOrFail();

        foreach ([
            ['planned_quantity' => -1, 'available_quantity' => 0, 'used_quantity' => 0],
            ['planned_quantity' => 1, 'available_quantity' => 2, 'used_quantity' => 0],
            ['planned_quantity' => 2, 'available_quantity' => 1, 'used_quantity' => 2],
        ] as $invalid) {
            try {
                $resource->fill($invalid)->save();
                $this->fail('Expected invalid execution resource quantities to be rejected.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame(
                    'Execution resource quantities must satisfy 0 <= used <= available <= planned.',
                    $exception->getMessage(),
                );
                $resource->refresh();
            }
        }
    }

    public function test_manager_can_update_resource_inside_active_organization(): void
    {
        [$organization, $version] = $this->publishedVersion(['Torniquete']);
        $execution = app(ScenarioExecutionManager::class)->create($version);
        $resource = $execution->resources()->firstOrFail();
        $this->authenticate($organization);

        $this->patch(route('execution-resources.update', $resource), [
            'planned_quantity' => 5,
            'available_quantity' => 4,
            'used_quantity' => 2,
            'status' => 'available',
        ])->assertRedirect();

        $resource->refresh();
        $this->assertSame(5, $resource->planned_quantity);
        $this->assertSame(4, $resource->available_quantity);
        $this->assertSame(2, $resource->used_quantity);
    }

    private function publishedVersion(array $resources): array
    {
        $organization = Organization::create([
            'name' => 'Centro Recursos M3',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário Recursos',
            'environment' => 'Área urbana',
            'threat_level' => 'potencial',
            'casualties' => 8,
            'estimated_casualty_count' => 8,
            'mechanism' => 'Incidente simulado',
            'resources' => $resources,
            'learning_objectives' => ['Gestão de recursos'],
            'expected_actions' => ['Controlar disponibilidade'],
            'critical_errors' => ['Perder controle logístico'],
            'status' => 'draft',
        ]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => 8,
            'resources' => $resources,
            'learning_objectives' => ['Gestão de recursos'],
            'expected_actions' => ['Controlar disponibilidade'],
            'critical_errors' => ['Perder controle logístico'],
            'publication_status' => 'published',
        ]);

        return [$organization, $version];
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
