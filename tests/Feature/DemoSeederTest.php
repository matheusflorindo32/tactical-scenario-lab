<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\ExecutionAssessment;
use App\Models\Organization;
use App\Models\Scenario;
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
        $this->assertGreaterThanOrEqual(1, $secondCounts['finalized_assessments']);
        $this->assertGreaterThanOrEqual(1, $secondCounts['action_items']);
        $this->assertFalse(User::query()->where('email', 'not like', '%@example.test')->exists());
        $this->assertTrue(User::query()->where('email', 'demo.manager@example.test')->exists());
    }

    public function test_demo_seeder_refuses_to_run_in_production(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('DemoSeeder cannot run in production.');
            $this->seed(DemoSeeder::class);
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
            'finalized_assessments' => ExecutionAssessment::query()
                ->where('organization_id', $organizationId)
                ->where('status', 'finalized')
                ->count(),
            'action_items' => ActionItem::query()
                ->whereHas('assessment', fn ($query) => $query->where('organization_id', $organizationId))
                ->count(),
        ];
    }
}
