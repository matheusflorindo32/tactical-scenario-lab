<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VictimCohort extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'scenario_version_id',
        'label',
        'quantity',
        'profile',
        'triage_category',
        'characteristics',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'profile' => 'array',
            'characteristics' => 'array',
        ];
    }

    public function scenarioVersion(): BelongsTo
    {
        return $this->belongsTo(ScenarioVersion::class);
    }
}
