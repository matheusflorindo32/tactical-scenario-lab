<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExecutionParticipantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_team_and_participant_schema_exists(): void
    {
        $this->assertTrue(Schema::hasColumns('execution_teams', [
            'id', 'uuid', 'scenario_execution_id', 'label', 'description', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('execution_participants', [
            'id', 'uuid', 'scenario_execution_id', 'execution_team_id', 'person_id', 'role', 'created_at', 'updated_at',
        ]));
    }

    public function test_manager_can_add_team_and_active_institutional_participant(): void
    {
        [$organization, $execution] = $this->executionContext();
        $this->authenticate($organization);
        $person = $this->personWithMembership($organization, 'Operador Alfa');

        $this->post(route('execution-teams.store', $execution), [
            'label' => 'Equipe Alfa',
            'description' => 'Resposta inicial',
        ])->assertRedirect();

        $teamId = (int) \DB::table('execution_teams')->value('id');

        $this->post(route('execution-participants.store', $execution), [
            'person_id' => $person->id,
            'execution_team_id' => $teamId,
            'role' => 'Líder de equipe',
        ])->assertRedirect();

        $this->assertDatabaseHas('execution_teams', [
            'scenario_execution_id' => $execution->id,
            'label' => 'Equipe Alfa',
        ]);
        $this->assertDatabaseHas('execution_participants', [
            'scenario_execution_id' => $execution->id,
            'execution_team_id' => $teamId,
            'person_id' => $person->id,
            'role' => 'Líder de equipe',
        ]);
    }

    public function test_participant_must_have_active_membership_in_execution_organization(): void
    {
        [$organization, $execution] = $this->executionContext();
        $externalOrganization = Organization::create([
            'name' => 'Organização Externa Participantes',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $this->authenticate($organization);
        $externalPerson = $this->personWithMembership($externalOrganization, 'Pessoa Externa');

        $this->post(route('execution-participants.store', $execution), [
            'person_id' => $externalPerson->id,
            'role' => 'Observador',
        ])->assertForbidden();

        $this->assertDatabaseCount('execution_participants', 0);
    }

    public function test_team_from_another_execution_cannot_be_assigned_to_participant(): void
    {
        [$organization, $execution] = $this->executionContext();
        [, $otherExecution] = $this->executionContext($organization, 'Segundo cenário');
        $this->authenticate($organization);
        $person = $this->personWithMembership($organization, 'Operador Bravo');

        \DB::table('execution_teams')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'scenario_execution_id' => $otherExecution->id,
            'label' => 'Equipe de outra execução',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $foreignTeamId = (int) \DB::table('execution_teams')->where('scenario_execution_id', $otherExecution->id)->value('id');

        $this->post(route('execution-participants.store', $execution), [
            'person_id' => $person->id,
            'execution_team_id' => $foreignTeamId,
            'role' => 'Operador',
        ])->assertForbidden();

        $this->assertDatabaseCount('execution_participants', 0);
    }

    public function test_same_person_cannot_be_added_twice_to_same_execution(): void
    {
        [$organization, $execution] = $this->executionContext();
        $this->authenticate($organization);
        $person = $this->personWithMembership($organization, 'Operador Charlie');

        $payload = [
            'person_id' => $person->id,
            'role' => 'Operador',
        ];

        $this->post(route('execution-participants.store', $execution), $payload)
            ->assertRedirect();
        $this->post(route('execution-participants.store', $execution), $payload)
            ->assertSessionHasErrors('person_id');

        $this->assertDatabaseCount('execution_participants', 1);
    }

    private function executionContext(?Organization $organization = null, string $title = 'Cenário Participantes'): array
    {
        $organization ??= Organization::create([
            'name' => 'Centro Participantes M3',
            'kind' => 'company',
            'status' => 'active',
        ]);

        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Área urbana',
            'threat_level' => 'potencial',
            'casualties' => 20,
            'estimated_casualty_count' => 20,
            'mechanism' => 'Incidente simulado',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Coordenação'],
            'expected_actions' => ['Organizar equipe'],
            'critical_errors' => ['Falha de comando'],
            'status' => 'draft',
        ]);
        $version = ScenarioVersion::create([
            'scenario_id' => $scenario->id,
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => 20,
            'resources' => ['Rádio'],
            'learning_objectives' => ['Coordenação'],
            'expected_actions' => ['Organizar equipe'],
            'critical_errors' => ['Falha de comando'],
            'publication_status' => 'published',
        ]);
        $execution = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'draft',
        ]);

        return [$organization, $execution];
    }

    private function authenticate(Organization $organization): void
    {
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'scenario_manager',
            'abilities' => [AccessAbility::SCENARIOS_VIEW, AccessAbility::SCENARIOS_MANAGE],
            'granted_at' => now(),
        ]);
        $this->actingAs($user)->withSession(['active_organization_id' => $organization->id]);
    }

    private function personWithMembership(Organization $organization, string $name): Person
    {
        $person = Person::create([
            'display_name' => $name,
            'status' => 'active',
        ]);
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        return $person;
    }
}
