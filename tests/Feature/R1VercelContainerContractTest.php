<?php

namespace Tests\Feature;

use Tests\TestCase;

class R1VercelContainerContractTest extends TestCase
{
    public function test_vercel_staging_uses_php_runtime_instead_of_an_ignored_dockerfile(): void
    {
        $this->assertFileDoesNotExist(base_path('Dockerfile.vercel'));
        $this->assertFileExists(base_path('vercel.json'));
        $this->assertFileExists(base_path('api/index.php'));

        $config = json_decode(
            file_get_contents(base_path('vercel.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNull($config['framework'] ?? 'missing');
        $this->assertSame('npm run build', $config['buildCommand'] ?? null);
        $this->assertSame('public', $config['outputDirectory'] ?? null);
        $this->assertSame(
            'vercel-php@0.8.0',
            $config['functions']['api/index.php']['runtime'] ?? null,
        );

        $routes = $config['routes'] ?? [];
        $this->assertContains([
            'src' => '/build/(.*)',
            'dest' => '/build/$1',
        ], $routes);
        $this->assertContains([
            'src' => '/(.*)',
            'dest' => '/api/index.php',
        ], $routes);
    }

    public function test_vercel_php_entrypoint_redirects_laravel_writable_paths_to_tmp(): void
    {
        $entrypoint = file_get_contents(base_path('api/index.php'));

        $this->assertStringContainsString("'/tmp/views'", $entrypoint);
        $this->assertStringContainsString('APP_CONFIG_CACHE', $entrypoint);
        $this->assertStringContainsString('APP_EVENTS_CACHE', $entrypoint);
        $this->assertStringContainsString('APP_PACKAGES_CACHE', $entrypoint);
        $this->assertStringContainsString('APP_ROUTES_CACHE', $entrypoint);
        $this->assertStringContainsString('APP_SERVICES_CACHE', $entrypoint);
        $this->assertStringContainsString('LOG_CHANNEL', $entrypoint);
        $this->assertStringContainsString("require __DIR__.'/../public/index.php';", $entrypoint);
    }

    public function test_vercel_build_matches_the_node_version_used_by_ci_and_php_runtime(): void
    {
        $package = json_decode(
            file_get_contents(base_path('package.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('22.x', $package['engines']['node'] ?? null);

        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));
        $this->assertStringNotContainsString('Dockerfile.vercel', $workflow);
    }
}
