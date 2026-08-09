<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionParticipant extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'scenario_execution_id',
        'execution_team_id',
        'person_id',
        'organization_membership_id',
        'unit_id_snapshot',
        'unit_name_snapshot',
        'position_snapshot',
        'role',
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(ScenarioExecution::class, 'scenario_execution_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ExecutionTeam::class, 'execution_team_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'organization_membership_id')->withTrashed();
    }

    public function unitSnapshot(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id_snapshot');
    }
}
