<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ScenarioExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_execution_schema_exists_with_institutional_and_lifecycle_fields(): void
    {
        $this->assertTrue(Schema::hasTable('scenario_executions'));
        $this->assertTrue(Schema::hasColumns('scenario_executions', [
            'id',
            'uuid',
            'organization_id',
            'scenario_version_id',
            'sequence_number',
            'status',
            'started_at',
            'completed_at',
            'cancelled_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_same_published_version_can_have_multiple_execution_records(): void
    {
        [$scenario, $version] = $this->publishedVersion();

        $first = ScenarioExecution::create([
            'organization_id' => $scenario->organization_id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'draft',
        ]);
        $second = ScenarioExecution::create([
            'organization_id' => $scenario->organization_id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 2,
            'status' => 'draft',
        ]);

        $this->assertNotEmpty($first->uuid);
        $this->assertNotEmpty($second->uuid);
        $this->assertNotSame($first->uuid, $second->uuid);
        $this->assertCount(2, $version->fresh()->executions);
        $this->assertSame([1, 2], $version->fresh()->executions->pluck('sequence_number')->all());
    }

    public function test_execution_lifecycle_guards_are_explicit(): void
    {
        [$scenario, $version] = $this->publishedVersion();

        $execution = ScenarioExecution::create([
            'organization_id' => $scenario->organization_id,
            'scenario_version_id' => $version->id,
            'sequence_number' => 1,
            'status' => 'draft',
        ]);

        $this->assertTrue($execution->isDraft());
        $this->assertTrue($execution->canStart());
        $this->assertFalse($execution->canComplete());
        $this->assertTrue($execution->canCancel());

        $execution->update(['status' => 'running', 'started_at' => now()]);
        $execution->refresh();

        $this->assertTrue($execution->isRunning());
        $this->assertFalse($execution->canStart());
        $this->assertTrue($execution->canComplete());
        $this->assertTrue($execution->canCancel());

        $execution->update(['status' => 'completed', 'completed_at' => now()]);
        $execution->refresh();

        $this->assertTrue($execution->isCompleted());
        $this->assertFalse($execution->canStart());
        $this->assertFalse($execution->canComplete());
        $this->assertFalse($execution->canCancel());
    }

    private function publishedVersion(): array
    {
        $organization = Organization::create([
            'name' => 'Centro de Simulação M3',
            'kind' => 'training_center',
            'status' => 'active',
        ]);

        $scenario = Scenario::create([
            'organization_id' => $organization->id,
            'title' => 'Incidente multivítimas',
            'environment' => 'Terminal rodoviário',
            'threat_level' => 'potencial',
            'casualties' => 50,
            'estimated_casualty_count' => 50,
            'mechanism' => 'Colisão em cadeia',
            'resources' => ['Kit IFAK', 'Rádio'],
            'learning_objectives' => ['Organizar resposta inicial'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha de segurança de cena'],
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

        return [$scenario, $version];
    }
}
