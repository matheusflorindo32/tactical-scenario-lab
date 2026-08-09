<?php

namespace Tests\Feature;

use Tests\TestCase;

class M9CiReleaseContractTest extends TestCase
{
    public function test_primary_ci_targets_only_main_release_line(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

        $this->assertStringContainsString("push:\n    branches:\n      - main", $workflow);
        $this->assertStringContainsString("pull_request:\n    branches:\n      - main", $workflow);
        $this->assertStringNotContainsString('      - develop', $workflow);
        $this->assertStringNotContainsString('feature/phase-2-', $workflow);
    }

    public function test_release_pipeline_uses_current_node24_action_runtime(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

        $this->assertStringContainsString('actions/checkout@v6', $workflow);
        $this->assertStringContainsString('actions/setup-node@v6', $workflow);
        $this->assertStringNotContainsString('actions/checkout@v4', $workflow);
        $this->assertStringNotContainsString('actions/setup-node@v4', $workflow);
    }

    public function test_release_pipeline_retains_all_required_quality_gates(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

        $required = [
            'composer validate --strict',
            'composer audit --locked',
            'npm audit --audit-level=high',
            'npm run build',
            'php artisan migrate:fresh --force',
            'Provision least-privilege runtime role',
            'Verify M6 migration rollback and reapply',
            'Repeat M6 concurrency invariants',
            'php artisan test',
            'vendor/bin/pint --test',
        ];

        foreach ($required as $contract) {
            $this->assertStringContainsString($contract, $workflow, "Missing release gate: {$contract}");
        }
    }
}
