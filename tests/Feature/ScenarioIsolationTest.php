<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScenarioIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_listing_and_direct_access_are_scoped_to_active_organization(): void
    {
        [$activeOrganization, $externalOrganization] = $this->organizations();
        $this->authenticate($activeOrganization);

        $visible = $this->scenario($activeOrganization, 'Cenário da organização ativa');
        $external = $this->scenario($externalOrganization, 'Cenário externo');
        $legacy = $this->scenario(null, 'Cenário legado sem proprietário');

        $this->get(route('scenarios.index'))
            ->assertOk()
            ->assertSee($visible->title)
            ->assertDontSee($external->title)
            ->assertDontSee($legacy->title);

        $this->get(route('scenarios.show', $visible))->assertOk();
        $this->get(route('scenarios.show', $external))->assertForbidden();
        $this->get(route('scenarios.show', $legacy))->assertForbidden();
    }

    public function test_cross_organization_execution_and_evaluation_are_forbidden_without_mutation(): void
    {
        [$activeOrganization, $externalOrganization] = $this->organizations();
        $this->authenticate($activeOrganization);

        $externalDraft = $this->scenario($externalOrganization, 'Rascunho externo');
        $externalRunning = $this->scenario($externalOrganization, 'Execução externa', 'running');

        $this->post(route('scenarios.execute', $externalDraft))->assertForbidden();
        $this->assertSame('draft', $externalDraft->refresh()->status);
        $this->assertNull($externalDraft->started_at);

        $this->post(route('scenarios.evaluate', $externalRunning), [
            'score' => 91,
            'observed_critical_errors' => [],
        ])->assertForbidden();

        $externalRunning->refresh();
        $this->assertSame('running', $externalRunning->status);
        $this->assertNull($externalRunning->score);
        $this->assertNull($externalRunning->completed_at);
    }

    public function test_new_scenario_is_owned_by_active_organization(): void
    {
        [$activeOrganization] = $this->organizations();
        $this->authenticate($activeOrganization);

        $this->post(route('scenarios.store'), [
            'environment' => 'Ambiente urbano',
            'threat_level' => 'potencial',
            'casualties' => 1,
            'mechanism' => 'Trauma penetrante',
            'resources' => ['Kit IFAK'],
        ])->assertRedirect();

        $scenario = Scenario::latest('id')->firstOrFail();

        $this->assertSame($activeOrganization->id, $scenario->organization_id);
    }

    private function authenticate(Organization $organization): void
    {
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'scenario_manager',
            'abilities' => ['scenarios.view', 'scenarios.manage', 'evaluations.manage'],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);
    }

    private function organizations(): array
    {
        return [
            Organization::create([
                'name' => 'Organização Ativa de Cenários',
                'kind' => 'company',
                'status' => 'active',
            ]),
            Organization::create([
                'name' => 'Organização Externa de Cenários',
                'kind' => 'company',
                'status' => 'active',
            ]),
        ];
    }

    private function scenario(?Organization $organization, string $title, string $status = 'draft'): Scenario
    {
        return Scenario::create([
            'organization_id' => $organization?->id,
            'title' => $title,
            'environment' => 'Ambiente urbano',
            'threat_level' => 'potencial',
            'casualties' => 1,
            'mechanism' => 'Trauma penetrante',
            'resources' => ['IFAK'],
            'learning_objectives' => ['Validar isolamento institucional'],
            'expected_actions' => ['Aplicar fluxo esperado'],
            'critical_errors' => ['Erro crítico'],
            'status' => $status,
            'started_at' => $status === 'running' ? now() : null,
        ]);
    }
}
