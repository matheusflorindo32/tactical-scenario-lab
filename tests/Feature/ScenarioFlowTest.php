<?php

namespace Tests\Feature;

use App\Models\Scenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoria funcional — cobre landing, dashboard, listagem, criação,
 * seleção independente de recursos, ciclo de vida (draft → running
 * → completed), erros observados vs catálogo, persistência e guards
 * de status.
 */
class ScenarioFlowTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------
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

    // ================== 1–5. Smoke ==================

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

    // ================== 6–8. Recursos (bug histórico) ==================

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

    // ================== 9–10. Ciclo de vida ==================

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

        // Segunda tentativa: rejeita e mantém started_at original.
        $originalStart = $scenario->started_at;

        $this->post(route('scenarios.execute', $scenario))
            ->assertRedirect()
            ->assertSessionHas('error');

        $scenario->refresh();

        $this->assertSame('running', $scenario->status);
        $this->assertTrue(
            $scenario->started_at->equalTo($originalStart)
        );
    }

    public function test_completed_scenario_cannot_be_started_again(): void
    {
        $scenario = $this->startedScenario();

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => 85,
            'debrief_notes' => 'ok',
        ])->assertRedirect();

        $this->post(route('scenarios.execute', $scenario))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // ================== 11–14. Avaliação ==================

    public function test_evaluation_rejects_score_below_zero(): void
    {
        $scenario = $this->startedScenario();

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => -1,
        ])->assertSessionHasErrors('score');

        $this->assertSame(
            'running',
            $scenario->fresh()->status
        );
    }

    public function test_evaluation_rejects_score_above_100(): void
    {
        $scenario = $this->startedScenario();

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => 101,
        ])->assertSessionHasErrors('score');
    }

    public function test_valid_evaluation_completes_scenario(): void
    {
        $scenario = $this->startedScenario();

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => 87,
            'debrief_notes' => 'Boa comunicação; falhou em reavaliação após TQ.',
        ])->assertRedirect();

        $scenario->refresh();

        $this->assertSame('completed', $scenario->status);
        $this->assertSame(87, $scenario->score);
        $this->assertNotNull($scenario->completed_at);
    }

    public function test_debrief_notes_are_persisted(): void
    {
        $scenario = $this->startedScenario();

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => 60,
            'debrief_notes' => 'Equipe comunicou mal a prioridade de evacuação.',
        ])->assertRedirect();

        $this->assertSame(
            'Equipe comunicou mal a prioridade de evacuação.',
            $scenario->fresh()->debrief_notes
        );
    }

    // ================== 15–17. Erros observados vs catálogo ==================

    public function test_observed_errors_are_persisted_when_from_catalog(): void
    {
        $scenario = $this->startedScenario();

        $catalogItem = $scenario->critical_errors[0];

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => 70,
            'observed_critical_errors' => [$catalogItem],
        ])->assertRedirect();

        $this->assertSame(
            [$catalogItem],
            $scenario->fresh()->observed_critical_errors
        );
    }

    public function test_observed_errors_reject_items_outside_catalog(): void
    {
        $scenario = $this->startedScenario();

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => 70,
            'observed_critical_errors' => [
                'Erro inventado que não está no catálogo',
            ],
        ])->assertSessionHasErrors('observed_critical_errors.0');

        $this->assertSame(
            'running',
            $scenario->fresh()->status
        );
    }

    public function test_catalog_is_not_overwritten_by_observed_errors(): void
    {
        $scenario = $this->startedScenario();

        $originalCatalog = $scenario->critical_errors;

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => 90,
            'observed_critical_errors' => [$originalCatalog[0]],
        ])->assertRedirect();

        $this->assertSame(
            $originalCatalog,
            $scenario->fresh()->critical_errors
        );
    }

    // ================== Persistência pós-consulta ==================

    public function test_completed_scenario_persists_across_reload(): void
    {
        $scenario = $this->startedScenario();

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => 78,
            'debrief_notes' => 'Registro para relatório final.',
        ])->assertRedirect();

        $reloaded = Scenario::findOrFail($scenario->id);

        $this->assertSame('completed', $reloaded->status);
        $this->assertSame(78, $reloaded->score);
        $this->assertSame(
            'Registro para relatório final.',
            $reloaded->debrief_notes
        );

        // A view responde 200 e exibe os dados persistidos.
        $this->get(route('scenarios.show', $reloaded))
            ->assertOk()
            ->assertSee('78')
            ->assertSee('/100')
            ->assertSee('Registro para relatório final.');
    }

    public function test_draft_cannot_be_evaluated_directly(): void
    {
        $this->post('/scenarios', $this->validPayload())->assertRedirect();

        $scenario = Scenario::latest()->firstOrFail();

        $this->post(route('scenarios.evaluate', $scenario), [
            'score' => 90,
        ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(
            'draft',
            $scenario->fresh()->status
        );
    }

    // ================== Healthcheck ==================

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
