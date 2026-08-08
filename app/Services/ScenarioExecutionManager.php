<?php

namespace App\Services;

use App\Models\ScenarioExecution;
use App\Models\ScenarioVersion;
use Illuminate\Support\Facades\DB;
use LogicException;

final class ScenarioExecutionManager
{
    public function create(ScenarioVersion $version): ScenarioExecution
    {
        if ($version->publication_status !== 'published') {
            throw new LogicException('Only published scenario versions can be executed.');
        }

        return DB::transaction(function () use ($version): ScenarioExecution {
            $scenario = $version->scenario()
                ->lockForUpdate()
                ->firstOrFail();

            $nextSequenceNumber = ((int) ScenarioExecution::query()
                ->where('scenario_version_id', $version->id)
                ->max('sequence_number')) + 1;

            $execution = ScenarioExecution::create([
                'organization_id' => $scenario->organization_id,
                'scenario_version_id' => $version->id,
                'sequence_number' => $nextSequenceNumber,
                'status' => 'draft',
            ]);

            collect($version->resources ?? [])
                ->filter(fn ($name): bool => is_string($name) && trim($name) !== '')
                ->map(fn (string $name): string => trim($name))
                ->unique()
                ->each(function (string $name) use ($execution): void {
                    $execution->resources()->create([
                        'name' => $name,
                        'planned_quantity' => 1,
                        'available_quantity' => 1,
                        'used_quantity' => 0,
                        'status' => 'available',
                    ]);
                });

            return $execution;
        });
    }

    public function start(ScenarioExecution $execution): ScenarioExecution
    {
        return DB::transaction(function () use ($execution): ScenarioExecution {
            $locked = $this->lockExecution($execution);

            if (! $locked->canStart()) {
                throw new LogicException('Execution cannot be started from its current status.');
            }

            $locked->update([
                'status' => 'running',
                'started_at' => now(),
                'completed_at' => null,
                'cancelled_at' => null,
            ]);

            return $locked->fresh();
        });
    }

    public function complete(ScenarioExecution $execution): ScenarioExecution
    {
        return DB::transaction(function () use ($execution): ScenarioExecution {
            $locked = $this->lockExecution($execution);

            if (! $locked->canComplete()) {
                throw new LogicException('Execution cannot be completed from its current status.');
            }

            $locked->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    public function cancel(ScenarioExecution $execution): ScenarioExecution
    {
        return DB::transaction(function () use ($execution): ScenarioExecution {
            $locked = $this->lockExecution($execution);

            if (! $locked->canCancel()) {
                throw new LogicException('Execution cannot be cancelled from its current status.');
            }

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    private function lockExecution(ScenarioExecution $execution): ScenarioExecution
    {
        return ScenarioExecution::query()
            ->whereKey($execution->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
