<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class M8ForensicKnowledgeContractTest extends TestCase
{
    public function test_knowledge_routes_are_get_only_and_inside_authenticated_active_account_boundary(): void
    {
        foreach (['knowledge.index', 'knowledge.show'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Missing knowledge route {$routeName}.");
            $this->assertSame(['GET', 'HEAD'], $route->methods());
            $this->assertContains('auth', $route->middleware());
            $this->assertContains('account.active', $route->middleware());
        }
    }

    public function test_canonical_knowledge_surfaces_have_no_placeholder_links_or_source_path_controls(): void
    {
        foreach ([
            'views/knowledge/index.blade.php',
            'views/knowledge/show.blade.php',
            'views/components/contextual-help.blade.php',
            'views/components/sidebar.blade.php',
        ] as $path) {
            $content = file_get_contents(resource_path($path));

            $this->assertIsString($content);
            $this->assertStringNotContainsString('href="#"', $content, "Placeholder link remains in {$path}");
            $this->assertStringNotContainsString("href='#'", $content, "Placeholder link remains in {$path}");
            $this->assertStringNotContainsString('organization_id=', $content, "Tenant parameter leaked into {$path}");
            $this->assertStringNotContainsString('active_organization_id=', $content, "Session tenant parameter leaked into {$path}");
            $this->assertStringNotContainsString('name="file"', $content, "File-path control exposed in {$path}");
            $this->assertStringNotContainsString('name="path"', $content, "Path control exposed in {$path}");
        }
    }

    public function test_readme_design_system_and_phase_audit_describe_the_implemented_m8_contract(): void
    {
        $readme = file_get_contents(base_path('README.md'));
        $designSystem = file_get_contents(base_path('docs/DESIGN_SYSTEM.md'));
        $auditPath = base_path('docs/PHASE_M8_AUDIT.md');

        $this->assertIsString($readme);
        $this->assertIsString($designSystem);
        $this->assertFileExists($auditPath);

        $this->assertStringContainsString('Knowledge & Documentation Center', $readme);
        $this->assertStringContainsString('/knowledge', $readme);
        $this->assertStringContainsString('Git-versioned', $readme);
        $this->assertStringContainsString('sem CMS', $readme);
        $this->assertStringContainsString('sem IA/RAG', $readme);

        $this->assertStringContainsString('x-contextual-help', $designSystem);
        $this->assertStringContainsString('Base de Conhecimento', $designSystem);
        $this->assertStringContainsString('TOC', $designSystem);
        $this->assertStringContainsString('Markdown', $designSystem);
        $this->assertStringContainsString('WCAG 2.2 AA', $designSystem);

        $audit = file_get_contents($auditPath);
        $this->assertIsString($audit);
        $this->assertStringContainsString('Phase M8 Audit', $audit);
        $this->assertStringContainsString('no CMS', $audit);
        $this->assertStringContainsString('exact-head', $audit);
    }
}
