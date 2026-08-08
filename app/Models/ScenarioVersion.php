<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ScenarioVersion extends Model
{
    use HasPublicUuid;

    public const DEFINITION_FIELDS = [
        'environment',
        'threat_level',
        'mechanism',
        'estimated_casualty_count',
        'resources',
        'learning_objectives',
        'expected_actions',
        'critical_errors',
    ];

    protected $fillable = [
        'uuid',
        'scenario_id',
        'version_number',
        ...self::DEFINITION_FIELDS,
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

    protected static function booted(): void
    {
        static::updating(function (ScenarioVersion $version): void {
            $wasPublished = $version->getOriginal('publication_status') === 'published';

            if ($wasPublished && $version->isDirty(self::DEFINITION_FIELDS)) {
                throw new LogicException('Published scenario versions are immutable. Create a new version instead.');
            }
        });
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
