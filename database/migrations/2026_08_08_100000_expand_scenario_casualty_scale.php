<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenarios', function (Blueprint $table): void {
            $table->unsignedBigInteger('casualties')->change();
            $table->unsignedBigInteger('estimated_casualty_count')->nullable()->after('casualties');
        });

        DB::table('scenarios')
            ->whereNull('estimated_casualty_count')
            ->update(['estimated_casualty_count' => DB::raw('casualties')]);
    }

    public function down(): void
    {
        if (DB::table('scenarios')->where('casualties', '>', 255)->exists()) {
            throw new \RuntimeException('Cannot safely downgrade scenarios.casualties to TINYINT while values above 255 exist.');
        }

        Schema::table('scenarios', function (Blueprint $table): void {
            $table->dropColumn('estimated_casualty_count');
            $table->unsignedTinyInteger('casualties')->change();
        });
    }
};
