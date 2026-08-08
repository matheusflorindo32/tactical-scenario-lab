<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Reporting\InstitutionalFilter;
use App\Reporting\InstructorDashboardQuery;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstructorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_dashboard_uses_execution_and_assessment_work_queues(): void
    {
        $organization = Organization::create([
            'name' => 'Instrutor M5',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = $this->scenario($organization);
        $version = $this->version($scenario);

        $running = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'running',
            'started_at' => now()->subHour(),
        ]);
        ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 2,
            'status' => 'draft',
        ]);
        $completedWithoutAssessment = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 3,
            'status' => 'completed',
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(2),
        ]);
        $completedWithDraft = ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 4,
            'status' => 'completed',
            'started_at' => now()->subHours(4),
            'completed_at' => now()->subHours(3),
        ]);
        DB::table('execution_assessments')->insert([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'scenario_execution_id' => $completedWithDraft->id,
            'source' => 'm4',
            'status' => 'draft',
            'pass_threshold' => 70,
            'evaluator_adjustment' => 0,
            'automatic_fail' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $filter = InstitutionalFilter::fromRequest(request(), $organization->id);
        $data = app(InstructorDashboardQuery::class)->get($filter);

        $this->assertSame(1, $data['running_count']);
        $this->assertSame(1, $data['draft_execution_count']);
        $this->assertSame(1, $data['completed_without_assessment_count']);
        $this->assertSame(1, $data['draft_assessment_count']);
        $this->assertTrue($data['running_executions']->contains('id', $running->id));
        $this->assertTrue($data['completed_without_assessment']->contains('id', $completedWithoutAssessment->id));
    }

    public function test_dashboard_route_requires_scenarios_view(): void
    {
        $organization = Organization::create([
            'name' => 'Instrutor Auth M5',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'no_scenario_read',
            'abilities' => [AccessAbility::REPORTS_VIEW],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    private function scenario(Organization $organization): Scenario
    {
        return Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Cenário Instrutor',
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
            'resources' => ['Rádio'],
            'learning_objectives' => ['Coordenação'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha'],
            'publication_status' => 'published',
        ]);
    }
}
