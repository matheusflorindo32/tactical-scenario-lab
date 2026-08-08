<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro imutável de uma ação relevante.
 *
 * O payload deve ser sanitizado antes da persistência e nunca conter valores
 * integrais de documentos ou contatos.
 */
class AuditLog extends Model
{
    use HasPublicUuid;

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
            'payload' => 'array',
            'logged_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
