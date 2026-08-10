<?php

namespace Tests\Feature;

use Tests\TestCase;

class R1FailureRecoveryDrillContractTest extends TestCase
{
    public function test_r1_ci_drills_database_readiness_failure_and_recovery_without_touching_hosted_staging(): void
    {
        $workflow = file_get_contents(base_path('.github/workflows/tests.yml'));

        $this->assertStringContainsString('Drill PostgreSQL readiness failure and recovery', $workflow);
        $this->assertStringContainsString('docker stop "$postgres_container"', $workflow);
        $this->assertStringContainsString('/health/live', $workflow);
        $this->assertStringContainsString('/health/ready', $workflow);
        $this->assertStringContainsString('test "$live_status" = "200"', $workflow);
        $this->assertStringContainsString('test "$ready_down_status" = "503"', $workflow);
        $this->assertStringContainsString('docker start "$postgres_container"', $workflow);
        $this->assertStringContainsString('pg_isready', $workflow);
        $this->assertStringContainsString('test "$ready_recovered_status" = "200"', $workflow);
    }
}
