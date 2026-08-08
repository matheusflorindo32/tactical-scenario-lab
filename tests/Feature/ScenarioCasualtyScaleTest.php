<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

        if (Schema::hasTable('scenario_victims')) {
            $this->assertDatabaseCount('scenario_victims', 0);
        }
    }

    public function test_create_form_explains_scalable_estimate_without_legacy_cap(): void
    {
        $this->authenticateScenarioManager();

        $this->get(route('scenarios.create'))
            ->assertOk()
            ->assertSee('Estimativa total de vítimas')
            ->assertSee('name="estimated_casualty_count"', false)
            ->assertDontSee('Uma a dez', false)
            ->assertDontSee('max="10"', false)
            ->assertDontSee('Informe entre 1 e 10 vítimas.', false);
    }

    public function test_scenario_show_distinguishes_total_estimate_from_detailed_representations(): void
    {
        $this->authenticateScenarioManager();

        $this->post(route('scenarios.store'), $this->payload(1000))
            ->assertSessionHasNoErrors();

        $scenario = Scenario::latest('id')->firstOrFail();

        $this->get(route('scenarios.show', $scenario))
            ->assertOk()
            ->assertSee('Estimativa total de vítimas')
            ->assertSee('Representações detalhadas')
            ->assertSee('0 individuais')
            ->assertSee('0 cohorts');
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
