<?php

namespace Tests\Feature;

use Tests\TestCase;

class M7ForensicUiContractTest extends TestCase
{
    public function test_canonical_authenticated_ui_has_no_placeholder_navigation_or_legacy_branding(): void
    {
        foreach ($this->canonicalUiFiles() as $path) {
            $content = file_get_contents(resource_path($path));

            $this->assertIsString($content);
            $this->assertStringNotContainsString('href="#"', $content, "Placeholder link remains in {$path}");
            $this->assertStringNotContainsString("href='#'", $content, "Placeholder link remains in {$path}");
            $this->assertStringNotContainsString('Tactical Medicine Academy', $content, "Legacy branding remains in {$path}");
            $this->assertDoesNotMatchRegularExpression(
                '/(?:text|bg|border|ring)-amber-\d+/',
                $content,
                "Generic amber semantic class remains in {$path}",
            );
        }
    }

    public function test_every_custom_color_class_used_by_canonical_ui_has_a_defined_theme_token(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($css);

        foreach ($this->canonicalUiFiles() as $path) {
            $content = file_get_contents(resource_path($path));

            $this->assertIsString($content);
            preg_match_all(
                '/(?:text|bg|border|ring)-(navy|stone|ink|emergency|clinical|alert)-(\d{2,3})/',
                $content,
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                $token = "--color-{$match[1]}-{$match[2]}:";
                $this->assertStringContainsString(
                    $token,
                    $css,
                    "Undefined design token {$token} referenced by {$path}",
                );
            }
        }
    }

    public function test_m7_design_system_and_readme_describe_the_implemented_contracts(): void
    {
        $designSystem = file_get_contents(base_path('docs/DESIGN_SYSTEM.md'));
        $readme = file_get_contents(base_path('README.md'));

        $this->assertIsString($designSystem);
        $this->assertIsString($readme);

        $this->assertStringContainsString('x-table', $designSystem);
        $this->assertStringContainsString('x-section-nav', $designSystem);
        $this->assertStringContainsString('x-attention-item', $designSystem);
        $this->assertStringContainsString('low-light', $designSystem);
        $this->assertStringContainsString('WCAG 2.2 AA', $designSystem);

        $this->assertStringContainsString('PostgreSQL', $readme);
        $this->assertStringContainsString('Tailwind CSS v4', $readme);
        $this->assertStringContainsString('Alpine.js', $readme);
        $this->assertStringContainsString('Operational Command Center', $readme);
        $this->assertStringNotContainsString('/scenarios/{scenario}/evaluate', $readme);
        $this->assertStringNotContainsString('Tailwind opcional', $readme);
    }

    private function canonicalUiFiles(): array
    {
        return [
            'views/components/sidebar.blade.php',
            'views/components/topbar.blade.php',
            'views/dashboard.blade.php',
            'views/dashboard/executive.blade.php',
            'views/scenarios/index.blade.php',
            'views/scenario-templates/index.blade.php',
            'views/history/executions.blade.php',
            'views/executions/show.blade.php',
            'views/assessments/show.blade.php',
            'views/people/index.blade.php',
            'views/organizations/index.blade.php',
            'views/access/index.blade.php',
        ];
    }
}
