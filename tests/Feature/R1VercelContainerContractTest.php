<?php

namespace Tests\Feature;

use Tests\TestCase;

class R1VercelContainerContractTest extends TestCase
{
    public function test_vercel_container_preserves_release_runtime_contract(): void
    {
        $path = base_path('Dockerfile.vercel');

        $this->assertFileExists($path);

        $dockerfile = file_get_contents($path);

        $this->assertStringContainsString('FROM node:22-alpine AS frontend', $dockerfile);
        $this->assertStringContainsString('npm ci', $dockerfile);
        $this->assertStringContainsString('npm run build', $dockerfile);
        $this->assertStringContainsString('FROM php:8.4-cli AS runtime', $dockerfile);
        $this->assertStringContainsString('docker-php-ext-install pdo_pgsql', $dockerfile);
        $this->assertStringContainsString('USER app', $dockerfile);
        $this->assertStringContainsString('public/build', $dockerfile);
        $this->assertStringContainsString('${PORT:-8080}', $dockerfile);
        $this->assertDoesNotMatchRegularExpression('/CMD[^\n]*migrate\s+--force/i', $dockerfile);
        $this->assertStringNotContainsString('database/database.sqlite', $dockerfile);
    }
}
