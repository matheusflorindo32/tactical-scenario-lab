<?php

namespace Tests\Feature;

use Tests\TestCase;

class R1HostedPreviewAdmissionContractTest extends TestCase
{
    public function test_r1_has_a_secret_safe_exact_deployment_preview_admission_workflow(): void
    {
        $path = base_path('.github/workflows/r1-preview-admission.yml');

        $this->assertFileExists($path);

        $workflow = file_get_contents($path);

        $this->assertStringContainsString('deployment_status:', $workflow);
        $this->assertStringContainsString('github.event.deployment_status.target_url', $workflow);
        $this->assertStringContainsString('github.event.deployment.sha', $workflow);
        $this->assertStringContainsString('VERCEL_AUTOMATION_BYPASS_SECRET', $workflow);
        $this->assertStringContainsString('secrets.VERCEL_AUTOMATION_BYPASS_SECRET', $workflow);
        $this->assertStringContainsString('x-vercel-protection-bypass', $workflow);
        $this->assertStringContainsString('x-vercel-set-bypass-cookie', $workflow);
        $this->assertStringContainsString('/health/live', $workflow);
        $this->assertStringContainsString('/health/ready', $workflow);
        $this->assertStringContainsString('Sustained readiness observation window', $workflow);
        $this->assertStringNotContainsString('your_bypass_secret_here', $workflow);
    }
}
