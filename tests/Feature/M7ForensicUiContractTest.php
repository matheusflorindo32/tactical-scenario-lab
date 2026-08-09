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
                '/(?:ink-(?:200|600|800)|emergency-(?:200|800)|clinical-(?:200|800)|amber-\d+)/',
                $content,
                "Undefined/legacy design token remains in {$path}",
            );
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
