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
            'database.default' => 'sqlite',
            'database.connections.pgsql.sslmode' => 'disable',
            'privacy.fingerprint_key' => '',
            'production.require_secure_session' => true,
            'session.secure' => false,
        ]);

        $this->artisan('production:preflight')
            ->expectsOutputToContain('APP_KEY is required.')
            ->expectsOutputToContain('PII_FINGERPRINT_KEY is required.')
            ->expectsOutputToContain('APP_DEBUG must be false.')
            ->expectsOutputToContain('DB_CONNECTION must be pgsql.')
            ->expectsOutputToContain('DB_SSLMODE must not be disable.')
            ->expectsOutputToContain('SESSION_SECURE_COOKIE must be true.')
            ->assertFailed();
    }

    public function test_production_preflight_accepts_safe_configuration(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:testing-application-key-material',
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'verify-full',
            'privacy.fingerprint_key' => 'testing-pii-fingerprint-secret',
            'production.require_secure_session' => true,
            'session.secure' => true,
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
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'require',
            'privacy.fingerprint_key' => $piiSecret,
            'production.require_secure_session' => true,
            'session.secure' => true,
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
            'database.default' => 'sqlite',
            'database.connections.pgsql.sslmode' => 'disable',
            'privacy.fingerprint_key' => '',
            'production.require_secure_session' => true,
            'session.secure' => false,
        ]);

        $validator = app(ProductionConfigurationValidator::class);

        $this->assertSame([], $validator->violations());
        $validator->assertSafe();
        $this->addToAssertionCount(1);
    }
}
