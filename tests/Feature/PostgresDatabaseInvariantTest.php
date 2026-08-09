<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PostgresRuntimeRole;
use Tests\TestCase;

class PostgresDatabaseInvariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL-specific database invariant test.');
        }
    }

    public function test_database_rejects_cross_organization_assessment_when_models_are_bypassed(): void
    {
        $execution = $this->execution();
        $foreignOrganization = Organization::create([
            'name' => 'Foreign organization for direct SQL',
            'kind' => 'company',
            'status' => 'active',
        ]);

        $this->expectException(QueryException::class);

        DB::table('execution_assessments')->insert([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $foreignOrganization->id,
            'scenario_execution_id' => $execution->id,
            'source' => 'm4',
            'status' => 'draft',
            'evaluator_adjustment' => 0,
            'automatic_fail' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_existing_scenario_version_sequence_uniqueness_is_already_database_enforced(): void
    {
        $execution = $this->execution();
        $version = $execution->scenarioVersion;

        $this->expectException(QueryException::class);

        DB::table('scenario_versions')->insert([
            'uuid' => (string) Str::uuid(),
            'scenario_id' => $version->scenario_id,
            'version_number' => $version->version_number,
            'environment' => 'Duplicate sequence probe',
            'threat_level' => $version->threat_level,
            'mechanism' => 'Probe',
            'estimated_casualty_count' => 1,
            'resources' => json_encode([]),
            'learning_objectives' => json_encode([]),
            'expected_actions' => json_encode([]),
            'critical_errors' => json_encode([]),
            'publication_status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_runtime_role_is_an_actual_non_privileged_login_and_not_table_owner_or_schema_creator(): void
    {
        $runtime = PostgresRuntimeRole::connection();

        $identity = $runtime->selectOne('SELECT current_user AS current_role, session_user AS session_role');
        $this->assertSame('tactical_runtime_test', $identity->current_role);
        $this->assertSame('tactical_runtime_test', $identity->session_role);

        $role = $runtime->selectOne(<<<'SQL'
            SELECT rolsuper, rolcreatedb, rolcreaterole
            FROM pg_roles
            WHERE rolname = current_user
            SQL);

        $this->assertFalse((bool) $role->rolsuper);
        $this->assertFalse((bool) $role->rolcreatedb);
        $this->assertFalse((bool) $role->rolcreaterole);

        $ownedTables = $runtime->selectOne(<<<'SQL'
            SELECT count(*) AS aggregate
            FROM pg_tables
            WHERE schemaname = 'public'
              AND tableowner = current_user
            SQL);

        $this->assertSame(0, (int) $ownedTables->aggregate);

        $this->expectException(QueryException::class);
        $runtime->statement('CREATE TABLE m6_runtime_forbidden (id integer)');
    }

    private function execution(): ScenarioExecution
    {
        $organization = Organization::create([
            'name' => 'M6 structural integrity organization',
            'kind' => 'company',
            'status' => 'active',
        ]);

        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'M6 structural integrity scenario',
            'environment' => 'Training area',
            'threat_level' => 'controlada',
            'casualties' => 1,
            'estimated_casualty_count' => 1,
            'mechanism' => 'Simulation',
            'resources' => [],
            'learning_objectives' => ['Integrity'],
            'expected_actions' => ['Preserve tenant relation'],
            'critical_errors' => ['Cross-tenant relation'],
            'status' => 'draft',
        ]);

        $version = $scenario->versions()->create([
            'version_number' => 1,
            'environment' => $scenario->environment,
            'threat_level' => $scenario->threat_level,
            'mechanism' => $scenario->mechanism,
            'estimated_casualty_count' => $scenario->estimated_casualty_count,
            'resources' => $scenario->resources,
            'learning_objectives' => $scenario->learning_objectives,
            'expected_actions' => $scenario->expected_actions,
            'critical_errors' => $scenario->critical_errors,
            'publication_status' => 'published',
        ]);

        return ScenarioExecution::create([
            'organization_id' => $organization->id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }
}
