<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

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

    protected static function booted(): void
    {
        static::saving(function (VictimCohort $cohort): void {
            if ((int) $cohort->quantity < 1) {
                throw new InvalidArgumentException('Victim cohort quantity must be at least 1.');
            }
        });
    }

    public function scenarioVersion(): BelongsTo
    {
        return $this->belongsTo(ScenarioVersion::class);
    }
}
