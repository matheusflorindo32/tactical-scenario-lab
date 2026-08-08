<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class DebriefEntry extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'uuid',
        'execution_debrief_id',
        'kind',
        'content',
        'position',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (DebriefEntry $entry): void {
            $debrief = ExecutionDebrief::query()
                ->with('assessment')
                ->findOrFail($entry->execution_debrief_id);
            $assessment = $debrief->assessment;

            if ($assessment->isFinalized()) {
                throw new LogicException('Finalized assessment debrief is immutable.');
            }

            if (trim((string) $entry->content) === '') {
                throw new InvalidArgumentException('Debrief entry content is required.');
            }

            $kind = (string) $entry->kind;
            $allowed = ['fact', 'interpretation', 'recommendation'];

            if ($assessment->source === 'legacy') {
                $allowed[] = 'legacy_unstructured';
            }

            if (! in_array($kind, $allowed, true)) {
                throw new InvalidArgumentException('Debrief entry kind is not allowed for this assessment source.');
            }
        });

        static::deleting(function (DebriefEntry $entry): void {
            $assessment = $entry->debrief()->firstOrFail()->assessment()->firstOrFail();

            if ($assessment->isFinalized()) {
                throw new LogicException('Finalized assessment debrief is immutable.');
            }
        });
    }

    public function debrief(): BelongsTo
    {
        return $this->belongsTo(ExecutionDebrief::class, 'execution_debrief_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
