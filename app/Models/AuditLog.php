<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog — trilha polimórfica de ações sensíveis. Ver §8 do plano.
 *
 * A gravação real de logs deve passar por um Service (AuditService) que
 * padronize a construção de action/subject/payload. Este model é apenas o
 * transporte persistente.
 */
class AuditLog extends Model
{
    use HasUuids;

    /** Não usamos `updated_at`: audit é imutável. */
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'organization_id',
        'actor_type',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'payload',
        'ip_address',
        'user_agent',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'   => 'array',
            'logged_at' => 'datetime',
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
