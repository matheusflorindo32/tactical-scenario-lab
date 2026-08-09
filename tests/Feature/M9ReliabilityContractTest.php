<?php

namespace Tests\Feature;

use Tests\TestCase;

class M9ReliabilityContractTest extends TestCase
{
    public function test_ci_proves_laravel_config_and_route_cacheability(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

        $required = [
            'php artisan config:cache',
            'php artisan route:cache',
            'php artisan config:clear',
            'php artisan route:clear',
        ];

        foreach ($required as $command) {
            $this->assertStringContainsString($command, $workflow, "Missing cacheability command: {$command}");
        }
    }

    public function test_release_health_contract_remains_minimal_and_secret_safe(): void
    {
        $this->getJson('/health/live')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);

        $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ready',
                'database' => 'ok',
            ]);
    }
}
