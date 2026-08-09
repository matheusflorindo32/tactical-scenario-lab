<?php

namespace Tests\Feature;

use Tests\TestCase;

class M9SecurityGovernanceTest extends TestCase
{
    public function test_security_policy_describes_current_release_posture(): void
    {
        $security = mb_strtolower(file_get_contents(base_path('SECURITY.md')));

        $this->assertStringContainsString('autenticação', $security);
        $this->assertStringContainsString('organização', $security);
        $this->assertStringContainsString('isolamento', $security);
        $this->assertStringContainsString('pii', $security);
        $this->assertStringContainsString('base de conhecimento', $security);
        $this->assertStringContainsString('docs/production.md', $security);
        $this->assertStringNotContainsString('autenticação (planejada', $security);
    }

    public function test_ci_runs_non_suppressive_dependency_security_audits(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));
        $lower = mb_strtolower($workflow);

        $this->assertStringContainsString('composer audit', $workflow);
        $this->assertStringContainsString('npm audit --audit-level=high', $workflow);

        $this->assertStringNotContainsString('npm audit fix', $lower);
        $this->assertStringNotContainsString('npm audit --fix', $lower);
        $this->assertStringNotContainsString('composer audit --ignore', $lower);
        $this->assertStringNotContainsString('audit.ignore', $lower);
    }
}
