<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->nullOnDelete();

            $table->index(['organization_id', 'status'], 'scenarios_org_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropIndex('scenarios_org_status_idx');
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
