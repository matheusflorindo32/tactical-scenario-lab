<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ExecutionEvent extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'scenario_execution_id',
        'execution_team_id',
        'execution_participant_id',
        'kind',
        'occurred_at',
        'summary',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Execution timeline events are append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('Execution timeline events are append-only.');
        });
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(ScenarioExecution::class, 'scenario_execution_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ExecutionTeam::class, 'execution_team_id');
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(ExecutionParticipant::class, 'execution_participant_id');
    }
}
