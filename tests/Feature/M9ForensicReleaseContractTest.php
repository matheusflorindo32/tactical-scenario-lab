<?php

namespace Tests\Feature;

use Tests\TestCase;

class M9ForensicReleaseContractTest extends TestCase
{
    public function test_final_release_artifacts_are_present_and_current(): void
    {
        $this->assertFileExists(base_path('docs/PHASE_M9_AUDIT.md'));
        $this->assertFileExists(base_path('docs/RELEASE.md'));

        $security = mb_strtolower(file_get_contents(base_path('SECURITY.md')));
        $env = file_get_contents(base_path('.env.example'));
        $config = file_get_contents(base_path('config/app.php'));
        $readme = file_get_contents(base_path('README.md'));

        $this->assertStringNotContainsString('autenticação (planejada', $security);
        $this->assertStringContainsString('APP_NAME="Tactical Scenario Lab"', $env);
        $this->assertStringContainsString('APP_LOCALE=pt_BR', $env);
        $this->assertStringContainsString("env('APP_NAME', 'Tactical Scenario Lab')", $config);
        $this->assertStringContainsString("env('APP_LOCALE', 'pt_BR')", $config);
        $this->assertStringContainsString('docs/RELEASE.md', $readme);
        $this->assertStringContainsString('CHANGELOG.md', $readme);
    }

    public function test_release_ci_builds_and_inspects_the_real_container(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

        $this->assertStringContainsString('Container — build and runtime contract', $workflow);
        $this->assertStringContainsString('docker build --tag tactical-scenario-lab:ci .', $workflow);
        $this->assertStringContainsString('docker run --rm tactical-scenario-lab:ci php -m', $workflow);
        $this->assertStringContainsString('test "$(id -u)" -ne 0', $workflow);
    }

    public function test_final_release_contracts_remain_non_suppressive_and_migration_safe(): void
    {
        $workflow = mb_strtolower(file_get_contents(base_path('.github/workflows/tests.yml')));
        $dockerfile = mb_strtolower(file_get_contents(base_path('Dockerfile')));

        $this->assertStringContainsString('composer audit --locked', $workflow);
        $this->assertStringContainsString('npm audit --audit-level=high', $workflow);
        $this->assertStringContainsString('php artisan config:cache', $workflow);
        $this->assertStringContainsString('php artisan route:cache', $workflow);
        $this->assertStringNotContainsString('npm audit fix', $workflow);
        $this->assertStringNotContainsString('composer audit --ignore', $workflow);

        $this->assertStringContainsString('pdo_pgsql', $dockerfile);
        $this->assertStringContainsString('user app', $dockerfile);
        $this->assertStringNotContainsString('database/database.sqlite', $dockerfile);
        $this->assertStringNotContainsString('cmd php artisan migrate', $dockerfile);
    }

    public function test_phase_audit_records_environment_limits_when_present(): void
    {
        $path = base_path('docs/PHASE_M9_AUDIT.md');

        if (! file_exists($path)) {
            $this->markTestIncomplete('M9 audit artifact is not implemented yet.');
        }

        $audit = mb_strtolower(file_get_contents($path));

        foreach (['exact-head', 'pixel', 'provider', 'restore', 'critical', 'high'] as $contract) {
            $this->assertStringContainsString($contract, $audit, "M9 audit missing forensic term: {$contract}");
        }
    }
}
