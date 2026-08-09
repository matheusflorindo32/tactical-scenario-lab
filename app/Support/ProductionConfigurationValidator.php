<?php

namespace App\Support;

use LogicException;

final class ProductionConfigurationValidator
{
    /**
     * @return list<string>
     */
    public function violations(): array
    {
        if (config('app.env') !== 'production') {
            return [];
        }

        $violations = [];

        if (blank(config('app.key'))) {
            $violations[] = 'APP_KEY is required.';
        }

        if (blank(config('privacy.fingerprint_key'))) {
            $violations[] = 'PII_FINGERPRINT_KEY is required.';
        }

        if ((bool) config('app.debug')) {
            $violations[] = 'APP_DEBUG must be false.';
        }

        if (config('database.default') !== 'pgsql') {
            $violations[] = 'DB_CONNECTION must be pgsql.';
        }

        if (config('database.connections.pgsql.sslmode') === 'disable') {
            $violations[] = 'DB_SSLMODE must not be disable.';
        }

        if ((bool) config('production.require_secure_session') && ! (bool) config('session.secure')) {
            $violations[] = 'SESSION_SECURE_COOKIE must be true.';
        }

        return $violations;
    }

    public function assertSafe(): void
    {
        $violations = $this->violations();

        if ($violations !== []) {
            throw new LogicException('Unsafe production configuration: '.implode(' ', $violations));
        }
    }
}
