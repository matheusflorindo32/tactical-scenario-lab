<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScenarioCasualtyScaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_accepts_supported_estimated_casualty_scales_without_artificial_cap(): void
    {
        $organization = $this->authenticateScenarioManager();

        foreach ([1, 11, 100, 1000] as $count) {
            $response = $this->post(route('scenarios.store'), $this->payload($count));

            $response->assertSessionHasNoErrors();

            $scenario = Scenario::query()
                ->where('organization_id', $organization->id)
                ->latest('id')
                ->firstOrFail();

            $this->assertSame($count, $scenario->estimated_casualty_count);
            $this->assertSame($count, $scenario->casualties);
        }
    }

    public function test_zero_and_negative_estimated_casualties_are_rejected(): void
    {
        $this->authenticateScenarioManager();

        foreach ([0, -1] as $count) {
            $this->post(route('scenarios.store'), $this->payload($count))
                ->assertSessionHasErrors('casualties');
        }
    }

    public function test_large_estimate_does_not_create_individual_victim_rows_implicitly(): void
    {
        $this->authenticateScenarioManager();

        $this->post(route('scenarios.store'), $this->payload(1000))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('scenarios', 1);

        if (\Illuminate\Support\Facades\Schema::hasTable('scenario_victims')) {
            $this->assertDatabaseCount('scenario_victims', 0);
        }
    }

    private function authenticateScenarioManager(): Organization
    {
        $organization = Organization::create([
            'name' => 'Centro de Simulação Escalável',
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

    private function payload(int $casualties): array
    {
        return [
            'environment' => 'Terminal rodoviário metropolitano',
            'threat_level' => 'potencial',
            'casualties' => $casualties,
            'mechanism' => 'Incidente com múltiplas vítimas',
            'resources' => ['Kit IFAK', 'Rádio'],
        ];
    }
}
