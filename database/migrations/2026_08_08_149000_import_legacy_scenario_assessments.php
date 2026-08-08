<?php

use App\Services\LegacyAssessmentImporter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(LegacyAssessmentImporter::class)->import();
    }

    public function down(): void
    {
        if (! Schema::hasTable('execution_assessments')) {
            return;
        }

        DB::table('execution_assessments')
            ->where('source', 'legacy')
            ->delete();
    }
};
