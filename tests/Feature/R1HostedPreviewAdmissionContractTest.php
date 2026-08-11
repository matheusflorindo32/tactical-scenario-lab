<?php

namespace Tests\Feature;

use Tests\TestCase;

class R1HostedPreviewAdmissionContractTest extends TestCase
{
    public function test_r1_primary_ci_has_a_secret_safe_exact_deployment_preview_admission_job(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

        $this->assertStringContainsString('deployments: read', $workflow);
        $this->assertStringContainsString('id-token: write', $workflow);
        $this->assertStringContainsString('Hosted Preview — exact deployment admission', $workflow);
        $this->assertStringContainsString('R1_HEAD_SHA: ${{ github.event.pull_request.head.sha }}', $workflow);
        $this->assertStringContainsString('api.github.com/repos/${GITHUB_REPOSITORY}/deployments?sha=${R1_HEAD_SHA}', $workflow);
        $this->assertStringContainsString('/deployments/${candidate_id}/statuses?per_page=100', $workflow);
        $this->assertStringContainsString('actions/github-script@v8', $workflow);
        $this->assertStringContainsString('core.getIDToken()', $workflow);
        $this->assertStringContainsString('x-vercel-trusted-oidc-idp-token', $workflow);
        $this->assertStringContainsString('VERCEL_AUTOMATION_BYPASS_SECRET', $workflow);
        $this->assertStringContainsString('secrets.VERCEL_AUTOMATION_BYPASS_SECRET', $workflow);
        $this->assertStringContainsString('x-vercel-protection-bypass', $workflow);
        $this->assertStringContainsString('x-vercel-set-bypass-cookie', $workflow);
        $this->assertStringContainsString('/health/live', $workflow);
        $this->assertStringContainsString('/health/ready', $workflow);
        $this->assertStringContainsString('https://vercel.com/sso-api', $workflow);
        $this->assertStringContainsString('OIDC health admission passed while the HTML root remained protected by Vercel SSO.', $workflow);
        $this->assertStringContainsString('Automation bypass admitted the protected HTML root.', $workflow);
        $this->assertStringContainsString('Sustained readiness observation window', $workflow);
        $this->assertStringContainsString('No supported protected Preview authentication method succeeded', $workflow);
        $this->assertStringNotContainsString('your_bypass_secret_here', $workflow);
    }
}
