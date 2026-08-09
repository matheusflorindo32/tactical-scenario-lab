<?php

namespace Tests\Feature;

use Tests\TestCase;

class M9ContainerContractTest extends TestCase
{
    public function test_production_container_has_postgres_assets_and_runtime_user_contract(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertStringContainsString('pdo_pgsql', $dockerfile);
        $this->assertStringNotContainsString('database/database.sqlite', $dockerfile);
        $this->assertStringNotContainsString('CMD php artisan migrate', $dockerfile);
        $this->assertStringNotContainsString('CMD ["sh", "-c", "php artisan migrate', $dockerfile);

        $this->assertStringContainsString('FROM node:22', $dockerfile);
        $this->assertStringContainsString('AS frontend', $dockerfile);
        $this->assertStringContainsString('npm ci', $dockerfile);
        $this->assertStringContainsString('npm run build', $dockerfile);
        $this->assertStringContainsString('COPY --from=frontend /app/public/build /var/www/html/public/build', $dockerfile);
        $this->assertStringContainsString('USER app', $dockerfile);
        $this->assertStringContainsString('composer install --no-dev --optimize-autoloader', $dockerfile);
    }

    public function test_production_runbook_separates_migration_and_runtime_phases(): void
    {
        $production = mb_strtolower(file_get_contents(base_path('docs/PRODUCTION.md')));

        $this->assertStringContainsString('production:preflight', $production);
        $this->assertStringContainsString('migrate --force', $production);
        $this->assertStringContainsString('migration', $production);
        $this->assertStringContainsString('runtime', $production);
        $this->assertStringContainsString('least-privilege', $production);
    }
}
