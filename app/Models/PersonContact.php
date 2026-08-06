<?php

namespace App\Models;

use App\Support\Normalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonContact extends Model
{
    protected $fillable = [
        'person_id',
        'organization_id',
        'type',
        'value',
        'value_normalized',
        'label',
        'is_primary',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary'  => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PersonContact $model) {
            $model->value_normalized = Normalizer::contact(
                $model->type,
                $model->value,
            );
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function masked(): string
    {
        if ($this->type === 'phone' || $this->type === 'emergency') {
            return Normalizer::maskPhone($this->value);
        }
        if ($this->type === 'email' && str_contains((string) $this->value, '@')) {
            [$local, $domain] = explode('@', $this->value, 2);
            $head = substr($local, 0, 2);
            return $head . str_repeat('*', max(0, strlen($local) - 2)) . '@' . $domain;
        }
        return $this->value ?? '***';
    }
}
