<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutionParticipantAttributionBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_org_membership_cannot_be_snapshotted(): void
    {
        [$actor, $organization, $execution] = $this->executionContext();
        $foreignOrganization = Organization::create([
            'name' => 'Instituição Externa M5',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $person = Person::create(['display_name' => 'Pessoa Compartilhada Fictícia', 'status' => 'active']);
        $foreignMembership = OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $foreignOrganization->id,
            'position' => 'Externo',
            'started_at' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($actor)
            ->withSession(['active_organization_id' => $organization->id])
            ->post(route('execution-participants.store', $execution), [
                'organization_membership_uuid' => $foreignMembership->uuid,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('execution_participants', 0);
    }

    public function test_legacy_person_only_request_rejects_ambiguous_active_memberships(): void
    {
        [$actor, $organization, $execution] = $this->executionContext();
        $person = Person::create(['display_name' => 'Pessoa Multi Vínculo Fictícia', 'status' => 'active']);

        foreach (['Instrutor', 'Avaliador'] as $position) {
            OrganizationMembership::create([
                'person_id' => $person->id,
                'organization_id' => $organization->id,
                'position' => $position,
                'started_at' => now()->subDay()->toDateString(),
                'status' => 'active',
            ]);
        }

        $this->actingAs($actor)
            ->withSession(['active_organization_id' => $organization->id])
            ->from(route('executions.show', $execution))
            ->post(route('execution-participants.store', $execution), [
                'person_uuid' => $person->uuid,
            ])
            ->assertRedirect(route('executions.show', $execution))
            ->assertSessionHasErrors('organization_membership_uuid');

        $this->assertDatabaseCount('execution_participants', 0);
    }

    public function test_unit_rename_after_link_does_not_rewrite_historical_label(): void
    {
        [$actor, $organization, $execution] = $this->executionContext();
        $unit = Unit::create([
            'organization_id' => $organization->id,
            'name' => 'Unidade Histórica Original',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $person = Person::create(['display_name' => 'Participante Histórico Fictício', 'status' => 'active']);
        $membership = OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'position' => 'Operador',
            'started_at' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($actor)
            ->withSession(['active_organization_id' => $organization->id])
            ->post(route('execution-participants.store', $execution), [
                'organization_membership_uuid' => $membership->uuid,
            ])
            ->assertRedirect();

        $participant = $execution->participants()->sole();
        $unit->update(['name' => 'Unidade Renomeada Depois']);
        $membership->update(['position' => 'Supervisor']);

        $this->assertSame('Unidade Histórica Original', $participant->fresh()->unit_name_snapshot);
        $this->assertSame('Operador', $participant->fresh()->position_snapshot);
    }

    public function test_execution_cockpit_uses_membership_selector_for_historical_context(): void
    {
        [$actor, $organization, $execution] = $this->executionContext();
        $unit = Unit::create([
            'organization_id' => $organization->id,
            'name' => 'Unidade Selecionável',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $person = Person::create(['display_name' => 'Pessoa Selecionável Fictícia', 'status' => 'active']);
        $membership = OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'position' => 'Avaliador',
            'started_at' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($actor)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('executions.show', $execution))
            ->assertOk()
            ->assertSee('name="organization_membership_uuid"', false)
            ->assertSee($membership->uuid)
            ->assertSee('Unidade Selecionável')
            ->assertSee('Avaliador');
    }

    private function executionContext(): array
    {
        $organization = Organization::create([
            'name' => 'Instituição M5 Boundary '.fake()->uuid(),
            'kind' => 'company',
            'status' => 'active',
        ]);
        $actor = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $actor->id,
            'organization_id' => $organization->id,
            'role' => 'scenario_manager',
            'abilities' => [AccessAbility::SCENARIOS_VIEW, AccessAbility::SCENARIOS_MANAGE],
            'granted_at' => now(),
        ]);
        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário Boundary '.fake()->uuid(),
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 1,
            'estimated_casualty_count' => 1,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Coordenação'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha de comunicação'],
            'status' => 'draft',
        ]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => 1,
            'resources' => ['Rádio'],
            'learning_objectives' => ['Coordenação'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha de comunicação'],
            'publication_status' => 'published',
        ]);
        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'draft',
        ]);

        return [$actor, $organization, $execution];
    }
}
