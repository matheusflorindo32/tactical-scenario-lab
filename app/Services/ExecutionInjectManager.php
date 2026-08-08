<?php

namespace App\Services;

use App\Models\ExecutionInject;
use Illuminate\Support\Facades\DB;
use LogicException;

final class ExecutionInjectManager
{
    public function deliver(ExecutionInject $inject): ExecutionInject
    {
        return DB::transaction(function () use ($inject): ExecutionInject {
            $locked = ExecutionInject::query()
                ->whereKey($inject->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isPlanned()) {
                throw new LogicException('Only planned injects can be delivered.');
            }

            $execution = $locked->execution()->firstOrFail();

            if (! $execution->isRunning()) {
                throw new LogicException('Injects can only be delivered while the execution is running.');
            }

            $deliveredAt = now();

            $locked->update([
                'status' => 'delivered',
                'delivered_at' => $deliveredAt,
                'cancelled_at' => null,
            ]);

            $execution->events()->create([
                'kind' => 'inject',
                'occurred_at' => $deliveredAt,
                'summary' => 'Inject entregue: '.$locked->label,
                'metadata' => [
                    'inject_uuid' => $locked->uuid,
                ],
            ]);

            return $locked->fresh();
        });
    }

    public function cancel(ExecutionInject $inject): ExecutionInject
    {
        return DB::transaction(function () use ($inject): ExecutionInject {
            $locked = ExecutionInject::query()
                ->whereKey($inject->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isPlanned()) {
                throw new LogicException('Only planned injects can be cancelled.');
            }

            $execution = $locked->execution()->firstOrFail();

            if (! $execution->canConfigure()) {
                throw new LogicException('Injects can only be cancelled before execution closure.');
            }

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'delivered_at' => null,
            ]);

            return $locked->fresh();
        });
    }
}
