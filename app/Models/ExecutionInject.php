<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionInject extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'scenario_execution_id',
        'label',
        'content',
        'planned_offset_seconds',
        'status',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_offset_seconds' => 'integer',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(ScenarioExecution::class, 'scenario_execution_id');
    }

    public function isPlanned(): bool
    {
        return $this->status === 'planned';
    }
}
