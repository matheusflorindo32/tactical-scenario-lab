<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ExecutionDebrief extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'execution_assessment_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (ExecutionDebrief $debrief): void {
            if ($debrief->assessment()->firstOrFail()->isFinalized()) {
                throw new LogicException('Finalized assessment debrief is immutable.');
            }
        });

        static::deleting(function (ExecutionDebrief $debrief): void {
            if ($debrief->assessment()->firstOrFail()->isFinalized()) {
                throw new LogicException('Finalized assessment debrief is immutable.');
            }
        });
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ExecutionAssessment::class, 'execution_assessment_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DebriefEntry::class)->orderBy('position')->orderBy('id');
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class)->orderBy('id');
    }
}
