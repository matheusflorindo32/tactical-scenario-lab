<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Services\ScenarioVersionManager;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class ScenarioVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_versions_schema_exists_with_versioned_definition_fields(): void
    {
        $this->assertTrue(Schema::hasTable('scenario_versions'));
        $this->assertTrue(Schema::hasColumns('scenario_versions', [
            'id',
            'uuid',
            'scenario_id',
            'version_number',
            'environment',
            'threat_level',
            'mechanism',
            'estimated_casualty_count',
            'resources',
            'learning_objectives',
            'expected_actions',
            'critical_errors',
            'publication_status',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_new_scenario_creates_version_one_with_scalable_definition(): void
    {
        [$scenario, $version] = $this->createScenarioWithVersion(1000);

        $this->assertDatabaseHas('scenario_versions', [
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'estimated_casualty_count' => 1000,
            'publication_status' => 'draft',
        ]);

        $this->assertNotEmpty($version->uuid);
        $this->assertSame(1000, $version->estimated_casualty_count);
        $this->assertSame('Terminal intermodal', $version->environment);
        $this->assertTrue($scenario->latestVersion()->first()->is($version));
    }

    public function test_published_version_definition_is_immutable(): void
    {
        [, $version] = $this->createScenarioWithVersion(1000);
        $manager = app(ScenarioVersionManager::class);

        $manager->publish($version);
        $version->refresh();

        $this->assertSame('published', $version->publication_status);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Published scenario versions are immutable. Create a new version instead.');

        $version->update(['environment' => 'Ambiente alterado indevidamente']);
    }

    public function test_revision_of_published_version_creates_next_draft_without_mutating_history(): void
    {
        [$scenario, $versionOne] = $this->createScenarioWithVersion(1000);
        $manager = app(ScenarioVersionManager::class);
        $manager->publish($versionOne);

        $versionTwo = $manager->revise($versionOne->fresh(), [
            'environment' => 'Terminal intermodal ampliado',
            'estimated_casualty_count' => 2000,
        ]);

        $this->assertSame(2, $versionTwo->version_number);
        $this->assertSame('draft', $versionTwo->publication_status);
        $this->assertSame(2000, $versionTwo->estimated_casualty_count);
        $this->assertSame('Terminal intermodal ampliado', $versionTwo->environment);
        $this->assertSame($scenario->id, $versionTwo->scenario_id);
        $this->assertNotSame($versionOne->uuid, $versionTwo->uuid);

        $versionOne->refresh();
        $this->assertSame('published', $versionOne->publication_status);
        $this->assertSame(1000, $versionOne->estimated_casualty_count);
        $this->assertSame('Terminal intermodal', $versionOne->environment);
        $this->assertCount(2, $scenario->fresh()->versions);
        $this->assertTrue($scenario->fresh()->latestVersion()->first()->is($versionTwo));
    }

    public function test_revision_rejects_non_positive_estimated_casualty_count_at_domain_boundary(): void
    {
        [, $versionOne] = $this->createScenarioWithVersion(1000);
        $manager = app(ScenarioVersionManager::class);
        $manager->publish($versionOne);

        foreach ([0, -1] as $invalidCount) {
            try {
                $manager->revise($versionOne->fresh(), [
                    'estimated_casualty_count' => $invalidCount,
                ]);
                $this->fail('Expected non-positive estimated casualty count to be rejected.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame(
                    'Estimated casualty count must be at least 1.',
                    $exception->getMessage(),
                );
            }
        }

        $this->assertCount(1, $versionOne->scenario->fresh()->versions);
    }

    private function createScenarioWithVersion(int $estimatedCasualtyCount): array
    {
        $organization = $this->authenticateScenarioManager();

        $this->post(route('scenarios.store'), [
            'environment' => 'Terminal intermodal',
            'threat_level' => 'potencial',
            'estimated_casualty_count' => $estimatedCasualtyCount,
            'mechanism' => 'Colisão em cadeia com múltiplas vítimas',
            'resources' => ['Kit IFAK', 'Rádio'],
        ])->assertSessionHasNoErrors();

        $scenario = Scenario::query()
            ->where('organization_id', $organization->id)
            ->firstOrFail();
        $version = ScenarioVersion::query()
            ->where('scenario_id', $scenario->id)
            ->where('version_number', 1)
            ->firstOrFail();

        return [$scenario, $version];
    }

    private function authenticateScenarioManager(): Organization
    {
        $organization = Organization::create([
            'name' => 'Centro de Versionamento',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'scenario_manager',
            'abilities' => [AccessAbility::SCENARIOS_VIEW, AccessAbility::SCENARIOS_MANAGE],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);

        return $organization;
    }
}
