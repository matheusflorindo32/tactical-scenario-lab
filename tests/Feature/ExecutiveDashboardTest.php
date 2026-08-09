<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Reporting\ExecutiveDashboardQuery;
use App\Reporting\InstitutionalFilter;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExecutiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_executive_metrics_use_m4_truth_and_exclude_unknown_result_from_pass_rate(): void
    {
        $organization = $this->organization('Executivo M5');
        $scenario = $this->scenario($organization, 'Cenário Executivo', legacyScore: 5, criticalErrors: ['Catálogo não observado']);
        $version = $this->version($scenario);

        $passed = $this->execution($organization, $version, 1);
        $legacyUnknown = $this->execution($organization, $version, 2);
        $failed = $this->execution($organization, $version, 3);

        $this->assessment($organization, $passed, 92, 'passed', false, observedError: 'Erro realmente observado');
        $this->assessment($organization, $legacyUnknown, 50, null, false, 'legacy');
        $this->assessment($organization, $failed, 60, 'failed', true, observedError: 'Erro realmente observado');

        $foreignOrganization = $this->organization('Executivo Externo');
        $foreignScenario = $this->scenario($foreignOrganization, 'Cenário Externo', legacyScore: 100, criticalErrors: ['Erro externo']);
        $foreignVersion = $this->version($foreignScenario);
        $foreignExecution = $this->execution($foreignOrganization, $foreignVersion, 1);
        $this->assessment($foreignOrganization, $foreignExecution, 100, 'passed', false, observedError: 'Erro externo');

        $filter = InstitutionalFilter::fromRequest(request(), $organization->id);
        $data = app(ExecutiveDashboardQuery::class)->get($filter);

        $this->assertSame(3, $data['total_executions']);
        $this->assertSame(3, $data['completed_executions']);
        $this->assertSame(3, $data['finalized_assessments']);
        $this->assertEqualsWithDelta(67.33, $data['average_final_score'], 0.01);
        $this->assertSame(50.0, $data['pass_rate']);
        $this->assertSame(1, $data['automatic_fail_count']);
        $this->assertSame(2, $data['top_observed_errors']->get('Erro realmente observado'));
        $this->assertFalse($data['top_observed_errors']->has('Catálogo não observado'));
        $this->assertFalse($data['top_observed_errors']->has('Erro externo'));
    }

    public function test_executive_dashboard_requires_reports_view(): void
    {
        $organization = $this->organization('Executivo Auth M5');
        $user = User::factory()->create(['status' => 'active']);
        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'viewer',
            'abilities' => [AccessAbility::SCENARIOS_VIEW],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/dashboard/executive')
            ->assertForbidden();
    }

    private function organization(string $name): Organization
    {
        return Organization::create(['name' => $name, 'kind' => 'company', 'status' => 'active']);
    }

    private function scenario(Organization $organization, string $title, int $legacyScore, array $criticalErrors): Scenario
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
            'critical_errors' => $criticalErrors,
            'status' => 'completed',
            'score' => $legacyScore,
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

    private function assessment(
        Organization $organization,
        ScenarioExecution $execution,
        float $score,
        ?string $result,
        bool $automaticFail,
        string $source = 'm4',
        ?string $observedError = null,
    ): int {
        $assessmentId = (int) DB::table('execution_assessments')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'scenario_execution_id' => $execution->id,
            'source' => $source,
            'status' => 'draft',
            'pass_threshold' => $source === 'm4' ? 70 : null,
            'base_score' => $score,
            'penalty_points' => 0,
            'evaluator_adjustment' => 0,
            'final_score' => $score,
            'result' => $result,
            'automatic_fail' => $automaticFail,
            'finalized_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($observedError !== null) {
            $this->criticalError($assessmentId, $observedError);
        }

        DB::table('execution_assessments')->where('id', $assessmentId)->update([
            'status' => 'finalized',
            'finalized_at' => now(),
            'updated_at' => now(),
        ]);

        return $assessmentId;
    }

    private function criticalError(int $assessmentId, string $label): void
    {
        DB::table('critical_error_occurrences')->insert([
            'uuid' => (string) Str::uuid(),
            'execution_assessment_id' => $assessmentId,
            'catalog_label_snapshot' => $label,
            'rule' => 'record',
            'penalty_points' => 0,
            'observed_at' => now()->subMinutes(90),
            'source' => 'legacy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
