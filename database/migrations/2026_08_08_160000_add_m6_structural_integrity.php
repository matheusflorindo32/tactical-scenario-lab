<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $mismatchedAssessments = DB::table('execution_assessments as assessments')
            ->join('scenario_executions as executions', 'executions.id', '=', 'assessments.scenario_execution_id')
            ->whereColumn('assessments.organization_id', '!=', 'executions.organization_id')
            ->count();

        if ($mismatchedAssessments > 0) {
            throw new \RuntimeException(
                "Cannot enforce M6 assessment tenant integrity: {$mismatchedAssessments} mismatched row(s) require explicit remediation.",
            );
        }

        DB::statement(
            'ALTER TABLE scenario_executions ADD CONSTRAINT scenario_executions_id_organization_unique UNIQUE (id, organization_id)',
        );

        DB::statement(
            'ALTER TABLE execution_assessments ADD CONSTRAINT execution_assessments_execution_organization_fk FOREIGN KEY (scenario_execution_id, organization_id) REFERENCES scenario_executions (id, organization_id) ON DELETE CASCADE',
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'ALTER TABLE execution_assessments DROP CONSTRAINT IF EXISTS execution_assessments_execution_organization_fk',
        );

        DB::statement(
            'ALTER TABLE scenario_executions DROP CONSTRAINT IF EXISTS scenario_executions_id_organization_unique',
        );
    }
};
