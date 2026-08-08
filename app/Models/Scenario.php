<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scenario extends Model
{
    protected $fillable = [
        'organization_id',
        'title',
        'environment',
        'threat_level',
        'casualties',
        'estimated_casualty_count',
        'mechanism',
        'resources',
        'learning_objectives',
        'expected_actions',
        // Catálogo de erros gerado pelo ScenarioGenerator (o que MONITORAR).
        'critical_errors',
        // Erros que o instrutor MARCOU como realmente cometidos na execução.
        'observed_critical_errors',
        'status',
        'score',
        'debrief_notes',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'resources' => 'array',
            'learning_objectives' => 'array',
            'expected_actions' => 'array',
            'critical_errors' => 'array',
            'observed_critical_errors' => 'array',
            'casualties' => 'integer',
            'estimated_casualty_count' => 'integer',
            'score' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /* ---------------------------------------------------------------
       Guards de ciclo de vida.
       O controller deve consultá-los antes de mutar `status`.
       --------------------------------------------------------------- */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /** Só pode iniciar a partir de rascunho. */
    public function canBeStarted(): bool
    {
        return $this->isDraft();
    }

    /** Avaliação aceita durante execução e após conclusão (edição). */
    public function canBeEvaluated(): bool
    {
        return $this->isRunning() || $this->isCompleted();
    }
}
