<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExecutionTeam extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'scenario_execution_id',
        'label',
        'description',
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(ScenarioExecution::class, 'scenario_execution_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExecutionParticipant::class);
    }
}
