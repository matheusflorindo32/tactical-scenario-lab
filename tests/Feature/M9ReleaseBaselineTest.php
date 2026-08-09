<?php

namespace Tests\Feature;

use Tests\TestCase;

class M9ReleaseBaselineTest extends TestCase
{
    public function test_release_metadata_matches_current_product(): void
    {
        $security = file_get_contents(base_path('SECURITY.md'));
        $env = file_get_contents(base_path('.env.example'));
        $composer = json_decode(
            file_get_contents(base_path('composer.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $dockerfile = file_get_contents(base_path('Dockerfile'));
        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

        $this->assertStringNotContainsString('autenticação (planejada', mb_strtolower($security));
        $this->assertStringNotContainsString('fase de mvp', mb_strtolower($security));

        $this->assertStringContainsString('APP_NAME="Tactical Scenario Lab"', $env);
        $this->assertStringContainsString('APP_LOCALE=pt_BR', $env);
        $this->assertStringContainsString('APP_FALLBACK_LOCALE=pt_BR', $env);
        $this->assertStringContainsString('APP_FAKER_LOCALE=pt_BR', $env);

        $this->assertArrayHasKey('description', $composer);
        $this->assertStringContainsString('Tactical Scenario Lab', $composer['description']);
        $this->assertStringNotContainsString('skeleton application', mb_strtolower($composer['description']));

        $this->assertStringNotContainsString('database/database.sqlite', $dockerfile);
        $this->assertDoesNotMatchRegularExpression('/CMD[^\n]*migrate\s+--force/i', $dockerfile);

        $this->assertStringNotContainsString('feature/phase-2-', $workflow);
    }
}
