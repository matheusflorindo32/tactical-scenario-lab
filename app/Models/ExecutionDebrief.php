<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExecutionDebrief extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'execution_assessment_id',
    ];

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
