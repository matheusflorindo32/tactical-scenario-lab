<?php

namespace Tests\Feature;

use Tests\TestCase;

class R1BrowserGateContractTest extends TestCase
{
    public function test_r1_has_a_two_engine_authenticated_browser_smoke_gate(): void
    {
        $this->assertFileExists(base_path('playwright.config.js'));
        $this->assertFileExists(base_path('tests/Browser/r1-smoke.spec.js'));
        $this->assertFileExists(base_path('database/seeders/R1BrowserSeeder.php'));

        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

        $this->assertStringContainsString('Browser smoke — Chromium + Firefox', $workflow);
        $this->assertStringContainsString('@playwright/test@1.62.0', $workflow);
        $this->assertStringContainsString('playwright install --with-deps chromium firefox', $workflow);
        $this->assertStringContainsString('R1BrowserSeeder', $workflow);
        $this->assertStringContainsString('npx playwright test', $workflow);

        $config = file_get_contents(base_path('playwright.config.js'));
        $this->assertStringContainsString("name: 'chromium'", $config);
        $this->assertStringContainsString("name: 'firefox'", $config);

        $browserSmoke = file_get_contents(base_path('tests/Browser/r1-smoke.spec.js'));
        $this->assertStringContainsString('demo.viewer@example.test', $browserSmoke);
        $this->assertStringContainsString("'/scenarios/create'", $browserSmoke);
        $this->assertStringContainsString("'/people'", $browserSmoke);
        $this->assertStringContainsString("'/access'", $browserSmoke);
        $this->assertStringContainsString('toBe(403)', $browserSmoke);
    }
}
