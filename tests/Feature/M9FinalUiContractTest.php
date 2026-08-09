<?php

namespace Tests\Feature;

use Tests\TestCase;

class M9FinalUiContractTest extends TestCase
{
    public function test_application_fallback_identity_and_locale_are_release_consistent(): void
    {
        $config = file_get_contents(base_path('config/app.php'));
        $env = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString("env('APP_NAME', 'Tactical Scenario Lab')", $config);
        $this->assertStringContainsString("env('APP_LOCALE', 'pt_BR')", $config);
        $this->assertStringContainsString("env('APP_FALLBACK_LOCALE', 'pt_BR')", $config);
        $this->assertStringContainsString("env('APP_FAKER_LOCALE', 'pt_BR')", $config);

        $this->assertStringContainsString('APP_NAME="Tactical Scenario Lab"', $env);
        $this->assertStringContainsString('APP_LOCALE=pt_BR', $env);
    }

    public function test_authenticated_shell_preserves_release_accessibility_contracts(): void
    {
        $layout = file_get_contents(base_path('resources/views/components/layouts/app.blade.php'));
        $sidebar = file_get_contents(base_path('resources/views/components/sidebar.blade.php'));
        $css = file_get_contents(base_path('resources/css/app.css'));
        $js = file_get_contents(base_path('resources/js/app.js'));

        $this->assertStringContainsString('<html lang="pt-BR"', $layout);
        $this->assertStringContainsString("'title' => 'Tactical Scenario Lab'", $layout);
        $this->assertStringContainsString('href="#main"', $layout);
        $this->assertStringContainsString('id="main"', $layout);
        $this->assertStringNotContainsString('href="#"', $sidebar);
        $this->assertStringContainsString("'route' => 'knowledge.index'", $sidebar);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
        $this->assertStringContainsString("localStorage.getItem('tsl-theme')", $js);
        $this->assertStringContainsString("localStorage.setItem('tsl-theme'", $js);
    }
}
