<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\ExecutionAssessment;
use App\Models\ExecutionInject;
use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use App\Models\User;
use App\Services\ExecutionAssessmentManager;
use App\Services\ExecutionInjectManager;
use App\Services\ScenarioExecutionManager;
use App\Services\ScenarioVersionManager;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\Support\ConcurrentDatabaseOperation;
use Tests\TestCase;

class PostgresConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL-specific concurrency test.');
        }

        $this->assertTrue(function_exists('pcntl_fork'), 'pcntl must be available in PostgreSQL CI.');

        Artisan::call('migrate:fresh', ['--force' => true]);
        (new DemoSeeder)->run();
    }

    protected function tearDown(): void
    {
        if (app()->bound('db') && DB::getDriverName() === 'pgsql') {
            Artisan::call('migrate:fresh', ['--force' => true]);
        }

        parent::tearDown();
    }

    public function test_start_start_same_execution_has_exactly_one_winner(): void
    {
        $execution = app(ScenarioExecutionManager::class)->create($this->publishedVersion());

        $results = ConcurrentDatabaseOperation::run([
            fn (): string => app(ScenarioExecutionManager::class)
                ->start(ScenarioExecution::query()->findOrFail($execution->id))
                ->status,
            fn (): string => app(ScenarioExecutionManager::class)
                ->start(ScenarioExecution::query()->findOrFail($execution->id))
                ->status,
        ]);

        $this->assertOneSuccessOneLogicRejection($results);
        $this->assertSame('running', $execution->fresh()->status);
        $this->assertNotNull($execution->fresh()->started_at);
    }

    public function test_complete_cancel_same_running_execution_has_exactly_one_terminal_winner(): void
    {
        $manager = app(ScenarioExecutionManager::class);
        $execution = $manager->start($manager->create($this->publishedVersion()));

        $results = ConcurrentDatabaseOperation::run([
            fn (): string => app(ScenarioExecutionManager::class)
                ->complete(ScenarioExecution::query()->findOrFail($execution->id))
                ->status,
            fn (): string => app(ScenarioExecutionManager::class)
                ->cancel(ScenarioExecution::query()->findOrFail($execution->id))
                ->status,
        ]);

        $this->assertOneSuccessOneLogicRejection($results);

        $fresh = $execution->fresh();
        $this->assertContains($fresh->status, ['completed', 'cancelled']);
        $this->assertNotSame($fresh->completed_at !== null, $fresh->cancelled_at !== null);
    }

    public function test_concurrent_execution_creation_allocates_unique_sequential_numbers(): void
    {
        $version = $this->publishedVersion();
        $before = (int) ScenarioExecution::query()
            ->where('scenario_version_id', $version->id)
            ->max('sequence_number');

        $results = ConcurrentDatabaseOperation::run([
            fn (): array => $this->createExecutionResult($version->id),
            fn (): array => $this->createExecutionResult($version->id),
        ]);

        $this->assertSame(2, $this->successCount($results));
        $sequences = collect($results)->pluck('value.sequence_number')->map(fn ($value): int => (int) $value)->sort()->values()->all();
        $this->assertSame([$before + 1, $before + 2], $sequences);
        $this->assertSame(2, count(array_unique(collect($results)->pluck('value.id')->all())));
    }

    public function test_concurrent_revisions_allocate_unique_sequential_version_numbers(): void
    {
        $source = $this->publishedVersion();
        $before = (int) ScenarioVersion::query()
            ->where('scenario_id', $source->scenario_id)
            ->max('version_number');

        $results = ConcurrentDatabaseOperation::run([
            fn (): array => $this->reviseScenarioResult($source->id),
            fn (): array => $this->reviseScenarioResult($source->id),
        ]);

        $this->assertSame(2, $this->successCount($results));
        $versions = collect($results)->pluck('value.version_number')->map(fn ($value): int => (int) $value)->sort()->values()->all();
        $this->assertSame([$before + 1, $before + 2], $versions);
    }

    public function test_duplicate_assessment_finalization_has_one_winner_and_no_duplicate_content(): void
    {
        [$assessment, $evaluator] = $this->prepareFinalizableAssessment();
        $criteriaCount = $assessment->criteria()->count();
        $evidenceCount = DB::table('assessment_evidence as evidence')
            ->join('assessment_criteria as criteria', 'criteria.id', '=', 'evidence.assessment_criterion_id')
            ->where('criteria.execution_assessment_id', $assessment->id)
            ->count();

        $results = ConcurrentDatabaseOperation::run([
            fn (): string => app(ExecutionAssessmentManager::class)
                ->finalize(ExecutionAssessment::query()->findOrFail($assessment->id), User::query()->findOrFail($evaluator->id))
                ->status,
            fn (): string => app(ExecutionAssessmentManager::class)
                ->finalize(ExecutionAssessment::query()->findOrFail($assessment->id), User::query()->findOrFail($evaluator->id))
                ->status,
        ]);

        $this->assertOneSuccessOneLogicRejection($results);
        $this->assertSame('finalized', $assessment->fresh()->status);
        $this->assertSame($criteriaCount, $assessment->criteria()->count());
        $this->assertSame($evidenceCount, DB::table('assessment_evidence as evidence')
            ->join('assessment_criteria as criteria', 'criteria.id', '=', 'evidence.assessment_criterion_id')
            ->where('criteria.execution_assessment_id', $assessment->id)
            ->count());
        $this->assertSame(3, $assessment->debrief()->firstOrFail()->entries()->count());
    }

    public function test_duplicate_inject_delivery_creates_exactly_one_timeline_event(): void
    {
        $manager = app(ScenarioExecutionManager::class);
        $execution = $manager->start($manager->create($this->publishedVersion()));
        $inject = ExecutionInject::create([
            'scenario_execution_id' => $execution->id,
            'label' => 'Race inject',
            'content' => 'Concurrent inject delivery test.',
            'planned_offset_seconds' => 0,
            'status' => 'planned',
        ]);

        $results = ConcurrentDatabaseOperation::run([
            fn (): string => app(ExecutionInjectManager::class)
                ->deliver(ExecutionInject::query()->findOrFail($inject->id))
                ->status,
            fn (): string => app(ExecutionInjectManager::class)
                ->deliver(ExecutionInject::query()->findOrFail($inject->id))
                ->status,
        ]);

        $this->assertOneSuccessOneLogicRejection($results);
        $this->assertSame('delivered', $inject->fresh()->status);
        $this->assertSame(1, $execution->events()
            ->where('kind', 'inject')
            ->where('summary', 'Inject entregue: Race inject')
            ->count());
    }

    public function test_stale_open_action_transition_cannot_overwrite_terminal_state(): void
    {
        $action = ActionItem::query()
            ->where('status', 'open')
            ->whereHas('debrief.assessment', fn ($query) => $query->where('status', 'finalized'))
            ->firstOrFail();
        $actor = User::query()->where('email', DemoSeeder::MANAGER_EMAIL)->firstOrFail();

        $results = ConcurrentDatabaseOperation::run([
            function () use ($action, $actor): string {
                $item = ActionItem::query()->findOrFail($action->id);
                $item->transitionTo('completed', User::query()->findOrFail($actor->id));

                return (string) $item->fresh()->status;
            },
            function () use ($action, $actor): string {
                $item = ActionItem::query()->findOrFail($action->id);
                $item->transitionTo('in_progress', User::query()->findOrFail($actor->id));

                return (string) $item->fresh()->status;
            },
        ]);

        $this->assertGreaterThanOrEqual(1, $this->successCount($results));
        $this->assertSame('completed', $action->fresh()->status);

        foreach ($results as $result) {
            if (! $result['ok']) {
                $this->assertSame(LogicException::class, $result['exception']);
            }
        }
    }

    private function publishedVersion(): ScenarioVersion
    {
        return ScenarioVersion::query()
            ->where('publication_status', 'published')
            ->orderBy('id')
            ->firstOrFail();
    }

    private function createExecutionResult(int $versionId): array
    {
        $execution = app(ScenarioExecutionManager::class)
            ->create(ScenarioVersion::query()->findOrFail($versionId));

        return [
            'id' => $execution->id,
            'sequence_number' => $execution->sequence_number,
        ];
    }

    private function reviseScenarioResult(int $sourceId): array
    {
        $version = app(ScenarioVersionManager::class)
            ->revise(ScenarioVersion::query()->findOrFail($sourceId));

        return [
            'id' => $version->id,
            'version_number' => $version->version_number,
        ];
    }

    private function prepareFinalizableAssessment(): array
    {
        $assessment = ExecutionAssessment::query()
            ->with(['execution.events', 'criteria.evidence', 'debrief.entries'])
            ->where('status', 'draft')
            ->firstOrFail();
        $evaluator = User::query()->where('email', DemoSeeder::MANAGER_EMAIL)->firstOrFail();
        $event = $assessment->execution->events->firstOrFail();

        foreach ($assessment->criteria as $criterion) {
            $criterion->update([
                'score' => 90,
                'evaluator_notes' => 'Prepared before deterministic finalization race.',
            ]);

            if ($criterion->evidence()->doesntExist()) {
                $criterion->evidence()->create([
                    'execution_event_id' => $event->id,
                    'statement' => 'Evidence prepared before deterministic finalization race.',
                    'observed_at' => $event->occurred_at,
                    'created_by_user_id' => $evaluator->id,
                ]);
            }
        }

        $debrief = $assessment->debrief()->firstOrCreate();

        if ($debrief->entries()->doesntExist()) {
            $debrief->entries()->createMany([
                [
                    'kind' => 'fact',
                    'content' => 'Concurrent finalization fact.',
                    'position' => 1,
                    'created_by_user_id' => $evaluator->id,
                ],
                [
                    'kind' => 'interpretation',
                    'content' => 'Concurrent finalization interpretation.',
                    'position' => 2,
                    'created_by_user_id' => $evaluator->id,
                ],
                [
                    'kind' => 'recommendation',
                    'content' => 'Concurrent finalization recommendation.',
                    'position' => 3,
                    'created_by_user_id' => $evaluator->id,
                ],
            ]);
        }

        return [$assessment->fresh(), $evaluator];
    }

    private function assertOneSuccessOneLogicRejection(array $results): void
    {
        $this->assertSame(1, $this->successCount($results));
        $failure = collect($results)->first(fn (array $result): bool => ! $result['ok']);
        $this->assertNotNull($failure);
        $this->assertSame(LogicException::class, $failure['exception']);
    }

    private function successCount(array $results): int
    {
        return collect($results)->filter(fn (array $result): bool => $result['ok'])->count();
    }
}
