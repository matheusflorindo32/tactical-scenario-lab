<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class ActionItem extends Model
{
    use HasPublicUuid;

    private bool $allowStatusTransition = false;

    protected $fillable = [
        'uuid',
        'execution_debrief_id',
        'action',
        'responsible_person_id',
        'responsible_label',
        'due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status_changed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ActionItem $item): void {
            $debrief = ExecutionDebrief::query()
                ->with('assessment')
                ->findOrFail($item->execution_debrief_id);
            $assessment = $debrief->assessment;

            if ($assessment->isFinalized() && ! $item->allowStatusTransition) {
                throw new LogicException('Finalized assessment action content is immutable.');
            }

            if ($item->allowStatusTransition) {
                return;
            }

            if (trim((string) $item->action) === '') {
                throw new InvalidArgumentException('Action item text is required.');
            }

            if (! $item->due_date) {
                throw new InvalidArgumentException('Action item deadline is required.');
            }

            $responsibleLabel = trim((string) $item->responsible_label);

            if (! $item->responsible_person_id && $responsibleLabel === '') {
                throw new InvalidArgumentException('Action item requires a responsible party.');
            }

            if ($item->responsible_person_id) {
                $person = Person::query()->findOrFail($item->responsible_person_id);
                $hasMembership = $person->isActive()
                    && $person->memberships()
                        ->where('organization_id', $assessment->organization_id)
                        ->where('status', 'active')
                        ->whereNull('ended_at')
                        ->exists();

                if (! $hasMembership) {
                    throw new InvalidArgumentException('Responsible person must have active membership in the assessment organization.');
                }
            }
        });

        static::deleting(function (ActionItem $item): void {
            $assessment = $item->debrief()->firstOrFail()->assessment()->firstOrFail();

            if ($assessment->isFinalized()) {
                throw new LogicException('Finalized assessment action content is immutable.');
            }
        });
    }

    public function debrief(): BelongsTo
    {
        return $this->belongsTo(ExecutionDebrief::class, 'execution_debrief_id');
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'responsible_person_id');
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by_user_id');
    }

    public function transitionTo(string $nextStatus, User $actor): void
    {
        DB::transaction(function () use ($nextStatus, $actor): void {
            $locked = self::query()
                ->lockForUpdate()
                ->findOrFail($this->id);

            $allowed = [
                'open' => ['in_progress', 'completed', 'cancelled'],
                'in_progress' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => [],
            ];

            $current = (string) $locked->status;

            if (! array_key_exists($current, $allowed) || ! in_array($nextStatus, $allowed[$current], true)) {
                throw new LogicException('Invalid action item status transition.');
            }

            $locked->allowStatusTransition = true;
            $locked->setAttribute('status', $nextStatus);
            $locked->setAttribute('status_changed_at', now());
            $locked->setAttribute('status_changed_by_user_id', $actor->id);
            $locked->save();
        });
    }
}
