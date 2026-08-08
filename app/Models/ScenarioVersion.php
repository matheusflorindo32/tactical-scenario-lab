<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScenarioVersion extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'scenario_id',
        'version_number',
        'environment',
        'threat_level',
        'mechanism',
        'estimated_casualty_count',
        'resources',
        'learning_objectives',
        'expected_actions',
        'critical_errors',
        'publication_status',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'estimated_casualty_count' => 'integer',
            'resources' => 'array',
            'learning_objectives' => 'array',
            'expected_actions' => 'array',
            'critical_errors' => 'array',
        ];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function victims(): HasMany
    {
        return $this->hasMany(ScenarioVictim::class);
    }

    public function cohorts(): HasMany
    {
        return $this->hasMany(VictimCohort::class);
    }
}
