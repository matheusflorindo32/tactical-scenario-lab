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

            return ScenarioExecution::create([
                'organization_id' => $scenario->organization_id,
                'scenario_version_id' => $version->id,
                'sequence_number' => $nextSequenceNumber,
                'status' => 'draft',
            ]);
        });
    }

    public function start(ScenarioExecution $execution): ScenarioExecution
    {
        if (! $execution->canStart()) {
            throw new LogicException('Execution cannot be started from its current status.');
        }

        return DB::transaction(function () use ($execution): ScenarioExecution {
            $execution->update([
                'status' => 'running',
                'started_at' => now(),
                'completed_at' => null,
                'cancelled_at' => null,
            ]);

            return $execution->fresh();
        });
    }

    public function complete(ScenarioExecution $execution): ScenarioExecution
    {
        if (! $execution->canComplete()) {
            throw new LogicException('Execution cannot be completed from its current status.');
        }

        return DB::transaction(function () use ($execution): ScenarioExecution {
            $execution->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $execution->fresh();
        });
    }

    public function cancel(ScenarioExecution $execution): ScenarioExecution
    {
        if (! $execution->canCancel()) {
            throw new LogicException('Execution cannot be cancelled from its current status.');
        }

        return DB::transaction(function () use ($execution): ScenarioExecution {
            $execution->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return $execution->fresh();
        });
    }
}
