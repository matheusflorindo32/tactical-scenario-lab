<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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
        $organization = $this->authenticateScenarioManager();

        $this->post(route('scenarios.store'), [
            'environment' => 'Terminal intermodal',
            'threat_level' => 'potencial',
            'estimated_casualty_count' => 1000,
            'mechanism' => 'Colisão em cadeia com múltiplas vítimas',
            'resources' => ['Kit IFAK', 'Rádio'],
        ])->assertSessionHasNoErrors();

        $scenario = Scenario::query()
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $this->assertDatabaseHas('scenario_versions', [
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'estimated_casualty_count' => 1000,
            'publication_status' => 'draft',
        ]);

        $version = ScenarioVersion::query()->where('scenario_id', $scenario->id)->firstOrFail();

        $this->assertNotEmpty($version->uuid);
        $this->assertSame(1000, $version->estimated_casualty_count);
        $this->assertSame('Terminal intermodal', $version->environment);
        $this->assertTrue($scenario->latestVersion()->first()->is($version));
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
