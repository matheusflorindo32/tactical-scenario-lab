<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const EXECUTION_ORGANIZATION_UNIQUE = 'scenario_executions_id_organization_unique';
    private const ASSESSMENT_EXECUTION_ORGANIZATION_FK = 'execution_assessments_execution_organization_fk';

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

        DB::statement(sprintf(
            'ALTER TABLE scenario_executions ADD CONSTRAINT %s UNIQUE (id, organization_id)',
            self::EXECUTION_ORGANIZATION_UNIQUE,
        ));

        DB::statement(sprintf(
            'ALTER TABLE execution_assessments ADD CONSTRAINT %s FOREIGN KEY (scenario_execution_id, organization_id) REFERENCES scenario_executions (id, organization_id) ON DELETE CASCADE',
            self::ASSESSMENT_EXECUTION_ORGANIZATION_FK,
        ));
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE execution_assessments DROP CONSTRAINT IF EXISTS %s',
            self::ASSESSMENT_EXECUTION_ORGANIZATION_FK,
        ));

        DB::statement(sprintf(
            'ALTER TABLE scenario_executions DROP CONSTRAINT IF EXISTS %s',
            self::EXECUTION_ORGANIZATION_UNIQUE,
        ));
    }
};
