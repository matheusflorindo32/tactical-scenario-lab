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
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExecutionParticipantAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_participant_has_historical_attribution_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('execution_participants', [
            'organization_membership_id',
            'unit_id_snapshot',
            'unit_name_snapshot',
            'position_snapshot',
        ]));
    }

    public function test_new_participant_snapshots_explicit_same_org_membership(): void
    {
        [$actor, $organization, $execution] = $this->executionContext();
        $unit = Unit::create([
            'organization_id' => $organization->id,
            'name' => 'Unidade Alfa',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $person = Person::create([
            'display_name' => 'Participante Fictício Alfa',
            'status' => 'active',
        ]);
        $membership = OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'position' => 'Instrutor',
            'started_at' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($actor)
            ->withSession(['active_organization_id' => $organization->id])
            ->post(route('execution-participants.store', $execution), [
                'person_uuid' => $person->uuid,
                'organization_membership_uuid' => $membership->uuid,
                'role' => 'Líder',
            ])
            ->assertRedirect();

        $participant = $execution->participants()->firstOrFail();

        $this->assertSame($membership->id, $participant->organization_membership_id);
        $this->assertSame($unit->id, $participant->unit_id_snapshot);
        $this->assertSame('Unidade Alfa', $participant->unit_name_snapshot);
        $this->assertSame('Instrutor', $participant->position_snapshot);
    }

    private function executionContext(): array
    {
        $organization = Organization::create([
            'name' => 'Instituição M5 Attribution',
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
            'title' => 'Cenário M5 Attribution',
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
