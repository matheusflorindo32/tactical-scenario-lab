<?php

namespace Tests\Feature;

use App\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoria funcional do Scenario legado: smoke, criação, recursos e
 * guards de lifecycle. Avaliação/debriefing pertence a ScenarioExecution
 * e é coberta pelas suítes Assessment* do M4.
 */
class ScenarioFlowTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'environment' => 'Ambiente urbano',
            'threat_level' => 'potencial',
            'casualties' => 1,
            'mechanism' => 'Ferimento penetrante',
            'resources' => ['Kit IFAK', 'Rádio'],
        ], $overrides);
    }

    private function startedScenario(array $overrides = []): Scenario
    {
        $this->post('/scenarios', $this->validPayload($overrides))->assertRedirect();

        $scenario = Scenario::latest()->firstOrFail();

        $this->post(route('scenarios.execute', $scenario))->assertRedirect();

        return $scenario->refresh();
    }

    public function test_landing_page_returns_200(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_dashboard_returns_200(): void
    {
        $this->get('/dashboard')->assertOk();
    }

    public function test_listing_returns_200(): void
    {
        $this->get('/scenarios')->assertOk();
    }

    public function test_create_form_returns_200(): void
    {
        $this->get('/scenarios/create')->assertOk();
    }

    public function test_scenario_can_be_created(): void
    {
        $this->post('/scenarios', $this->validPayload())->assertRedirect();

        $this->assertDatabaseHas('scenarios', [
            'environment' => 'Ambiente urbano',
            'status' => 'draft',
        ]);
    }

    public function test_single_resource_is_saved_independently(): void
    {
        $this->post('/scenarios', $this->validPayload([
            'resources' => ['Kit IFAK'],
        ]))->assertRedirect();

        $scenario = Scenario::latest()->firstOrFail();

        $this->assertIsArray($scenario->resources);
        $this->assertSame(['Kit IFAK'], $scenario->resources);
    }

    public function test_multiple_resources_are_saved(): void
    {
        $this->post('/scenarios', $this->validPayload([
            'resources' => ['Kit IFAK', 'Maca', 'DEA'],
        ]))->assertRedirect();

        $scenario = Scenario::latest()->firstOrFail();

        $this->assertSame(
            ['Kit IFAK', 'Maca', 'DEA'],
            $scenario->resources
        );
    }

    public function test_unselected_resources_are_not_saved(): void
    {
        $this->post('/scenarios', $this->validPayload([
            'resources' => ['Kit IFAK'],
        ]))->assertRedirect();

        $scenario = Scenario::latest()->firstOrFail();

        $this->assertNotContains('Maca', $scenario->resources ?? []);
        $this->assertNotContains('DEA', $scenario->resources ?? []);
    }

    public function test_duplicate_resources_are_rejected_by_validation(): void
    {
        $this->post('/scenarios', $this->validPayload([
            'resources' => ['Kit IFAK', 'Kit IFAK'],
        ]))->assertSessionHasErrors('resources.1');
    }

    public function test_scenario_starts_execution(): void
    {
        $this->post('/scenarios', $this->validPayload())->assertRedirect();

        $scenario = Scenario::latest()->firstOrFail();

        $this->post(route('scenarios.execute', $scenario))->assertRedirect();

        $scenario->refresh();

        $this->assertSame('running', $scenario->status);
        $this->assertNotNull($scenario->started_at);
    }

    public function test_scenario_cannot_start_twice(): void
    {
        $scenario = $this->startedScenario();
        $originalStart = $scenario->started_at;

        $this->post(route('scenarios.execute', $scenario))
            ->assertRedirect()
            ->assertSessionHas('error');

        $scenario->refresh();

        $this->assertSame('running', $scenario->status);
        $this->assertTrue($scenario->started_at->equalTo($originalStart));
    }

    public function test_completed_scenario_cannot_be_started_again(): void
    {
        $scenario = $this->startedScenario();
        $scenario->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->post(route('scenarios.execute', $scenario->fresh()))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('completed', $scenario->fresh()->status);
    }

    public function test_healthcheck_returns_json_ok(): void
    {
        $this->get('/health')
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'app',
                'version',
                'time',
            ]);
    }
}
