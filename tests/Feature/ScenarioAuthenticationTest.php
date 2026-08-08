<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScenarioAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_scenario_workflow(): void
    {
        $scenario = Scenario::create([
            'title' => 'Cenário protegido',
            'environment' => 'Ambiente urbano',
            'threat_level' => 'potencial',
            'casualties' => 1,
            'mechanism' => 'Ferimento penetrante',
            'resources' => ['Kit IFAK'],
            'learning_objectives' => ['Avaliar acesso protegido'],
            'expected_actions' => ['Aplicar fluxo correto'],
            'critical_errors' => ['Falha crítica'],
            'status' => 'draft',
        ]);

        $this->get(route('scenarios.index'))->assertRedirect(route('login'));
        $this->get(route('scenarios.create'))->assertRedirect(route('login'));
        $this->get(route('scenarios.show', $scenario))->assertRedirect(route('login'));
        $this->post(route('scenarios.store'), [])->assertRedirect(route('login'));
        $this->post(route('scenarios.execute', $scenario))->assertRedirect(route('login'));
    }

    public function test_authenticated_active_user_can_access_scenario_workflow(): void
    {
        $organization = Organization::create([
            'name' => 'Organização Cenários',
            'status' => 'active',
        ]);
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

        $this->get(route('scenarios.index'))->assertOk();
        $this->get(route('scenarios.create'))->assertOk();
    }
}
