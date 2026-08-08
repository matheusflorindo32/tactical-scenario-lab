<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioVictim extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'scenario_version_id',
        'code',
        'profile',
        'injuries',
        'initial_state',
        'expected_priority',
    ];

    protected function casts(): array
    {
        return [
            'profile' => 'array',
            'injuries' => 'array',
            'initial_state' => 'array',
        ];
    }

    public function scenarioVersion(): BelongsTo
    {
        return $this->belongsTo(ScenarioVersion::class);
    }
}
