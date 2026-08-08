<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class ScenarioVictimModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_victim_and_cohort_storage_contracts_exist(): void
    {
        $this->assertTrue(Schema::hasTable('scenario_victims'));
        $this->assertTrue(Schema::hasColumns('scenario_victims', [
            'id',
            'uuid',
            'scenario_version_id',
            'code',
            'profile',
            'injuries',
            'initial_state',
            'expected_priority',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('victim_cohorts'));
        $this->assertTrue(Schema::hasColumns('victim_cohorts', [
            'id',
            'uuid',
            'scenario_version_id',
            'label',
            'quantity',
            'profile',
            'triage_category',
            'characteristics',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_large_estimate_can_mix_few_individual_victims_with_aggregated_cohort(): void
    {
        [$organization, $version] = $this->createVersion(1000);

        $victimA = $version->victims()->create([
            'code' => 'V-001',
            'profile' => ['adult'],
            'injuries' => ['hemorragia maciça'],
            'initial_state' => ['consciente' => false],
            'expected_priority' => 'immediate',
        ]);
        $victimB = $version->victims()->create([
            'code' => 'V-002',
            'profile' => ['adult'],
            'injuries' => ['trauma torácico'],
            'initial_state' => ['consciente' => true],
            'expected_priority' => 'immediate',
        ]);
        $cohort = $version->cohorts()->create([
            'label' => 'Vítimas deambulantes',
            'quantity' => 998,
            'profile' => ['adultos e adolescentes'],
            'triage_category' => 'green',
            'characteristics' => ['deambulantes'],
        ]);

        $this->assertSame(1000, $version->estimated_casualty_count);
        $this->assertCount(2, $version->victims);
        $this->assertCount(1, $version->cohorts);
        $this->assertSame(998, $cohort->quantity);
        $this->assertNotEmpty($victimA->uuid);
        $this->assertNotEmpty($victimB->uuid);
        $this->assertNotEmpty($cohort->uuid);
        $this->assertSame($organization->id, $version->scenario->organization_id);
    }

    public function test_victim_cohort_quantity_must_be_at_least_one(): void
    {
        [, $version] = $this->createVersion(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Victim cohort quantity must be at least 1.');

        $version->cohorts()->create([
            'label' => 'Cohort inválido',
            'quantity' => 0,
        ]);
    }

    private function createVersion(int $estimatedCasualtyCount): array
    {
        $organization = Organization::create([
            'name' => 'Centro de Incidentes de Massa',
            'kind' => 'company',
            'status' => 'active',
        ]);

        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Incidente de massa',
            'environment' => 'Terminal metropolitano',
            'threat_level' => 'controlada',
            'casualties' => $estimatedCasualtyCount,
            'estimated_casualty_count' => $estimatedCasualtyCount,
            'mechanism' => 'Colisão múltipla',
            'resources' => [],
            'learning_objectives' => [],
            'expected_actions' => [],
            'critical_errors' => [],
            'status' => 'draft',
        ]);

        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => $estimatedCasualtyCount,
            'resources' => [],
            'learning_objectives' => [],
            'expected_actions' => [],
            'critical_errors' => [],
            'publication_status' => 'draft',
        ]);

        return [$organization, $version];
    }
}
