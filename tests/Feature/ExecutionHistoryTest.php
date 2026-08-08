<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExecutionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_requires_reports_view(): void
    {
        [$user, $organization] = $this->userContext([AccessAbility::SCENARIOS_VIEW]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/history/executions')
            ->assertForbidden();
    }

    public function test_history_is_tenant_safe_paginated_and_multi_unit_without_duplicate_execution(): void
    {
        [$user, $organization] = $this->userContext([AccessAbility::SCENARIOS_VIEW, AccessAbility::REPORTS_VIEW]);
        $unitA = Unit::create(['organization_id' => $organization->id, 'name' => 'Unidade Alfa', 'kind' => 'company', 'status' => 'active']);
        $unitB = Unit::create(['organization_id' => $organization->id, 'name' => 'Unidade Bravo', 'kind' => 'company', 'status' => 'active']);
        $scenario = $this->scenario($organization, 'Histórico Principal');
        $execution = $this->execution($organization, $this->version($scenario), 1);

        foreach ([[$unitA, 'Pessoa 1'], [$unitA, 'Pessoa 2'], [$unitB, 'Pessoa 3']] as [$unit, $label]) {
            $personId = DB::table('people')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'display_name' => $label,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('execution_participants')->insert([
                'uuid' => (string) Str::uuid(),
                'scenario_execution_id' => $execution->id,
                'person_id' => $personId,
                'unit_id_snapshot' => $unit->id,
                'unit_name_snapshot' => $unit->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $foreign = Organization::create(['name' => 'Histórico Externo', 'kind' => 'company', 'status' => 'active']);
        $foreignScenario = $this->scenario($foreign, 'Não pode aparecer');
        $this->execution($foreign, $this->version($foreignScenario), 1);

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/history/executions?unit_uuid='.$unitA->uuid);

        $response
            ->assertOk()
            ->assertSee('Histórico Principal')
            ->assertDontSee('Não pode aparecer')
            ->assertSee('Unidade Alfa')
            ->assertSee('Unidade Bravo')
            ->assertViewHas('executions', fn ($page) => $page->total() === 1 && $page->perPage() === 25);
    }

    private function userContext(array $abilities): array
    {
        $organization = Organization::create(['name' => 'Histórico M5 '.Str::uuid(), 'kind' => 'company', 'status' => 'active']);
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'reporter',
            'abilities' => $abilities,
            'granted_at' => now(),
        ]);

        return [$user, $organization];
    }

    private function scenario(Organization $organization, string $title): Scenario
    {
        return Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 1,
            'estimated_casualty_count' => 1,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Coordenação'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha'],
            'status' => 'draft',
        ]);
    }

    private function version(Scenario $scenario): ScenarioVersion
    {
        return ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => 1,
            'resources' => $scenario->resources,
            'learning_objectives' => $scenario->learning_objectives,
            'expected_actions' => $scenario->expected_actions,
            'critical_errors' => $scenario->critical_errors,
            'publication_status' => 'published',
        ]);
    }

    private function execution(Organization $organization, ScenarioVersion $version, int $sequence): ScenarioExecution
    {
        return ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => $sequence,
            'status' => 'completed',
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
    }
}
