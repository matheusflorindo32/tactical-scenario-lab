<?php

namespace Tests\Feature;

use Tests\TestCase;

class M7ThemeContractTest extends TestCase
{
    public function test_low_light_theme_is_browser_local_and_has_no_backend_contract(): void
    {
        $javascript = file_get_contents(resource_path('js/app.js'));
        $css = file_get_contents(resource_path('css/app.css'));
        $topbar = file_get_contents(resource_path('views/components/topbar.blade.php'));

        $this->assertIsString($javascript);
        $this->assertIsString($css);
        $this->assertIsString($topbar);

        $this->assertStringContainsString("Alpine.store('theme'", $javascript);
        $this->assertStringContainsString("localStorage.getItem('tsl-theme')", $javascript);
        $this->assertStringContainsString("localStorage.setItem('tsl-theme'", $javascript);
        $this->assertStringContainsString("dataset.theme", $javascript);
        $this->assertStringNotContainsString('fetch(', $javascript);
        $this->assertStringNotContainsString('axios.', $javascript);

        $this->assertStringContainsString("[data-theme='low-light']", $css);
        $this->assertStringContainsString('data-theme-toggle', $topbar);
        $this->assertStringContainsString("$store.theme.toggle()", $topbar);
        $this->assertStringContainsString('aria-pressed', $topbar);
    }

    public function test_authenticated_app_defaults_to_light_before_browser_preference_is_applied(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertIsString($layout);
        $this->assertStringContainsString('data-theme="light"', $layout);
    }
}
