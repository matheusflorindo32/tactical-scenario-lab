<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Gera um UUID público separado da chave primária interna.
 *
 * A aplicação mantém `id` BIGINT autoincremental para relacionamentos e usa
 * `uuid` apenas em URLs e integrações públicas. Esta trait não altera
 * $primaryKey, $keyType nem $incrementing.
 */
trait HasPublicUuid
{
    protected static function bootHasPublicUuid(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->getAttribute('uuid'))) {
                $model->setAttribute('uuid', (string) Str::uuid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
