<?php

namespace Tests\Feature;

use App\Support\ProductionConfigurationValidator;
use Tests\TestCase;

class ProductionConfigurationTest extends TestCase
{
    public function test_production_preflight_rejects_unsafe_security_critical_configuration(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => true,
            'app.key' => '',
            'app.url' => 'http://localhost',
            'database.default' => 'sqlite',
            'database.connections.pgsql.sslmode' => 'disable',
            'privacy.fingerprint_key' => '',
            'production.require_secure_session' => true,
            'session.secure' => false,
            'session.driver' => 'file',
            'cache.default' => 'file',
            'logging.default' => 'single',
            'logging.level' => 'debug',
        ]);

        $this->artisan('production:preflight')
            ->expectsOutputToContain('APP_KEY is required.')
            ->expectsOutputToContain('PII_FINGERPRINT_KEY is required.')
            ->expectsOutputToContain('APP_DEBUG must be false.')
            ->expectsOutputToContain('APP_URL must use HTTPS and must not point to localhost.')
            ->expectsOutputToContain('DB_CONNECTION must be pgsql.')
            ->expectsOutputToContain('DB_SSLMODE must be verify-full in production.')
            ->expectsOutputToContain('SESSION_SECURE_COOKIE must be true.')
            ->expectsOutputToContain('SESSION_DRIVER must use database or redis in production.')
            ->expectsOutputToContain('CACHE_STORE must use database or redis in production.')
            ->expectsOutputToContain('LOG_CHANNEL must emit to stderr in production.')
            ->expectsOutputToContain('LOG_LEVEL must not be debug in production.')
            ->assertFailed();
    }

    public function test_production_preflight_rejects_ssl_require_without_server_identity_verification(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:testing-application-key-material',
            'app.url' => 'https://tactical.example.test',
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'require',
            'privacy.fingerprint_key' => 'testing-pii-fingerprint-secret',
            'production.require_secure_session' => true,
            'session.secure' => true,
            'session.driver' => 'database',
            'cache.default' => 'database',
            'logging.default' => 'stderr',
            'logging.level' => 'info',
        ]);

        $this->artisan('production:preflight')
            ->expectsOutputToContain('DB_SSLMODE must be verify-full in production.')
            ->assertFailed();
    }

    public function test_production_preflight_accepts_safe_configuration(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:testing-application-key-material',
            'app.url' => 'https://tactical.example.test',
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'verify-full',
            'privacy.fingerprint_key' => 'testing-pii-fingerprint-secret',
            'production.require_secure_session' => true,
            'session.secure' => true,
            'session.driver' => 'database',
            'cache.default' => 'database',
            'logging.default' => 'stderr',
            'logging.level' => 'info',
        ]);

        $this->artisan('production:preflight')
            ->expectsOutput('Production configuration preflight passed.')
            ->assertSuccessful();
    }

    public function test_stack_logging_is_safe_when_stderr_is_in_the_stack(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:testing-application-key-material',
            'app.url' => 'https://tactical.example.test',
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'verify-full',
            'privacy.fingerprint_key' => 'testing-pii-fingerprint-secret',
            'production.require_secure_session' => true,
            'session.secure' => true,
            'session.driver' => 'redis',
            'cache.default' => 'redis',
            'logging.default' => 'stack',
            'logging.channels.stack.channels' => ['stderr'],
            'logging.level' => 'warning',
        ]);

        $this->artisan('production:preflight')
            ->expectsOutput('Production configuration preflight passed.')
            ->assertSuccessful();
    }

    public function test_preflight_never_outputs_secret_values(): void
    {
        $appSecret = 'base64:do-not-leak-application-secret';
        $piiSecret = 'do-not-leak-pii-secret';

        config([
            'app.env' => 'production',
            'app.debug' => true,
            'app.key' => $appSecret,
            'app.url' => 'https://tactical.example.test',
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'verify-full',
            'privacy.fingerprint_key' => $piiSecret,
            'production.require_secure_session' => true,
            'session.secure' => true,
            'session.driver' => 'database',
            'cache.default' => 'database',
            'logging.default' => 'stderr',
            'logging.level' => 'info',
        ]);

        $this->artisan('production:preflight')
            ->doesntExpectOutputToContain($appSecret)
            ->doesntExpectOutputToContain($piiSecret)
            ->expectsOutputToContain('APP_DEBUG must be false.')
            ->assertFailed();
    }

    public function test_validator_is_a_noop_outside_production(): void
    {
        config([
            'app.env' => 'testing',
            'app.debug' => true,
            'app.key' => '',
            'app.url' => 'http://localhost',
            'database.default' => 'sqlite',
            'database.connections.pgsql.sslmode' => 'disable',
            'privacy.fingerprint_key' => '',
            'production.require_secure_session' => true,
            'session.secure' => false,
            'session.driver' => 'file',
            'cache.default' => 'file',
            'logging.default' => 'single',
            'logging.level' => 'debug',
        ]);

        $validator = app(ProductionConfigurationValidator::class);

        $this->assertSame([], $validator->violations());
        $validator->assertSafe();
        $this->addToAssertionCount(1);
    }
}
