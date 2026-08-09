<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthReadinessTest extends TestCase
{
    public function test_liveness_is_public_minimal_and_does_not_touch_domain_state(): void
    {
        $this->getJson('/health/live')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
            ]);
    }

    public function test_readiness_reports_database_availability_with_minimal_contract(): void
    {
        $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ready',
                'database' => 'ok',
            ]);
    }

    public function test_readiness_failure_is_privacy_safe(): void
    {
        $this->configureUnavailableDatabase();

        $response = $this->getJson('/health/ready')
            ->assertStatus(503)
            ->assertExactJson([
                'status' => 'unavailable',
                'database' => 'unavailable',
            ]);

        $body = $response->getContent();

        foreach ([
            '127.0.0.1',
            'health_private_database',
            'health_secret_user',
            'health_secret_password',
            'select 1',
            'SQLSTATE',
            'PII_FINGERPRINT_KEY',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $body);
        }
    }

    public function test_health_probes_do_not_depend_on_database_backed_web_session_middleware(): void
    {
        config(['session.driver' => 'database']);
        $this->configureUnavailableDatabase();

        $this->getJson('/health/live')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
            ]);

        $this->getJson('/health/ready')
            ->assertStatus(503)
            ->assertExactJson([
                'status' => 'unavailable',
                'database' => 'unavailable',
            ]);
    }

    public function test_readiness_fails_closed_for_unsafe_production_configuration_without_exposing_violation_details(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        config([
            'app.env' => 'production',
            'app.debug' => true,
        ]);

        $response = $this->getJson('/health/ready')
            ->assertStatus(503)
            ->assertExactJson([
                'status' => 'unavailable',
                'database' => 'unavailable',
            ]);

        $body = $response->getContent();
        $this->assertStringNotContainsString('APP_KEY', $body);
        $this->assertStringNotContainsString('APP_DEBUG', $body);
        $this->assertStringNotContainsString('Unsafe production configuration', $body);
    }

    private function configureUnavailableDatabase(): void
    {
        config([
            'database.default' => 'health_unavailable',
            'database.connections.health_unavailable' => [
                'driver' => 'pgsql',
                'url' => null,
                'host' => '127.0.0.1',
                'port' => 1,
                'database' => 'health_private_database',
                'username' => 'health_secret_user',
                'password' => 'health_secret_password',
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'disable',
            ],
        ]);
        DB::purge('health_unavailable');
    }
}
