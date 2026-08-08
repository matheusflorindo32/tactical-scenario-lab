<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\CriticalErrorOccurrence;
use App\Models\ExecutionAssessment;
use App\Models\KeyTimeRecord;
use App\Models\Organization;
use App\Models\Scenario;
use App\Models\ScenarioExecution;
use App\Models\ScenarioTemplate;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_builds_complete_deterministic_fictional_graph(): void
    {
        $this->seed(DemoSeeder::class);

        $organization = Organization::query()->where('name', DemoSeeder::ORGANIZATION_NAME)->sole();
        $firstCounts = $this->demoCounts($organization->id);

        $this->seed(DemoSeeder::class);

        $organization->refresh();
        $secondCounts = $this->demoCounts($organization->id);

        $this->assertSame($firstCounts, $secondCounts);
        $this->assertSame(1, Organization::query()->where('name', DemoSeeder::ORGANIZATION_NAME)->count());
        $this->assertGreaterThanOrEqual(2, $secondCounts['units']);
        $this->assertGreaterThanOrEqual(3, $secondCounts['scenarios']);
        $this->assertGreaterThanOrEqual(1, $secondCounts['templates']);
        $this->assertGreaterThanOrEqual(1, $secondCounts['running_executions']);
        $this->assertGreaterThanOrEqual(2, $secondCounts['completed_executions']);
        $this->assertGreaterThanOrEqual(1, $secondCounts['draft_assessments']);
        $this->assertGreaterThanOrEqual(1, $secondCounts['finalized_assessments']);
        $this->assertGreaterThanOrEqual(1, $secondCounts['observed_errors']);
        $this->assertGreaterThanOrEqual(1, $secondCounts['key_times']);
        $this->assertGreaterThanOrEqual(2, $secondCounts['action_items']);
        $this->assertGreaterThanOrEqual(2, $secondCounts['action_statuses']);
        $this->assertFalse(User::query()->where('email', 'not like', '%@example.test')->exists());
        $this->assertTrue(User::query()->where('email', 'demo.manager@example.test')->exists());
    }

    public function test_demo_seeder_refuses_to_run_in_production(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('DemoSeeder cannot run in production.');
            (new DemoSeeder)->run();
        } finally {
            app()->detectEnvironment(fn (): string => 'testing');
        }
    }

    private function demoCounts(int $organizationId): array
    {
        return [
            'units' => Unit::query()->where('organization_id', $organizationId)->count(),
            'scenarios' => Scenario::query()->where('organization_id', $organizationId)->count(),
            'templates' => ScenarioTemplate::query()->where('organization_id', $organizationId)->count(),
            'running_executions' => ScenarioExecution::query()
                ->where('organization_id', $organizationId)
                ->where('status', 'running')
                ->count(),
            'completed_executions' => ScenarioExecution::query()
                ->where('organization_id', $organizationId)
                ->where('status', 'completed')
                ->count(),
            'draft_assessments' => ExecutionAssessment::query()
                ->where('organization_id', $organizationId)
                ->where('status', 'draft')
                ->count(),
            'finalized_assessments' => ExecutionAssessment::query()
                ->where('organization_id', $organizationId)
                ->where('status', 'finalized')
                ->count(),
            'observed_errors' => CriticalErrorOccurrence::query()
                ->whereHas('assessment', fn ($query) => $query->where('organization_id', $organizationId))
                ->count(),
            'key_times' => KeyTimeRecord::query()
                ->whereHas('assessment', fn ($query) => $query->where('organization_id', $organizationId))
                ->count(),
            'action_items' => ActionItem::query()
                ->whereHas('debrief.assessment', fn ($query) => $query->where('organization_id', $organizationId))
                ->count(),
            'action_statuses' => ActionItem::query()
                ->whereHas('debrief.assessment', fn ($query) => $query->where('organization_id', $organizationId))
                ->pluck('status')
                ->unique()
                ->count(),
        ];
    }
}
