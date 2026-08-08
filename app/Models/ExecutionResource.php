<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ExecutionResource extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'scenario_execution_id',
        'name',
        'planned_quantity',
        'available_quantity',
        'used_quantity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'integer',
            'available_quantity' => 'integer',
            'used_quantity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ExecutionResource $resource): void {
            $planned = (int) $resource->planned_quantity;
            $available = (int) $resource->available_quantity;
            $used = (int) $resource->used_quantity;

            if (
                $planned < 0
                || $available < 0
                || $used < 0
                || $used > $available
                || $available > $planned
            ) {
                throw new InvalidArgumentException(
                    'Execution resource quantities must satisfy 0 <= used <= available <= planned.',
                );
            }
        });
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(ScenarioExecution::class, 'scenario_execution_id');
    }
}
