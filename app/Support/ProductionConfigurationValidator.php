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

        if (! $this->hasSafeApplicationUrl((string) config('app.url'))) {
            $violations[] = 'APP_URL must use HTTPS and must not point to localhost.';
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

        if (! in_array(config('session.driver'), ['database', 'redis'], true)) {
            $violations[] = 'SESSION_DRIVER must use database or redis in production.';
        }

        if (! in_array(config('cache.default'), ['database', 'redis'], true)) {
            $violations[] = 'CACHE_STORE must use database or redis in production.';
        }

        if (! $this->emitsLogsToStderr()) {
            $violations[] = 'LOG_CHANNEL must emit to stderr in production.';
        }

        if ($this->productionLogLevel() === 'debug') {
            $violations[] = 'LOG_LEVEL must not be debug in production.';
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

    private function hasSafeApplicationUrl(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $scheme === 'https'
            && $host !== ''
            && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    private function emitsLogsToStderr(): bool
    {
        $channel = config('logging.default');

        if ($channel === 'stderr') {
            return true;
        }

        if ($channel !== 'stack') {
            return false;
        }

        return in_array('stderr', (array) config('logging.channels.stack.channels', []), true);
    }

    private function productionLogLevel(): string
    {
        $level = config('logging.level')
            ?? config('logging.channels.stderr.level')
            ?? config('logging.channels.'.config('logging.default').'.level')
            ?? 'debug';

        return strtolower((string) $level);
    }
}
