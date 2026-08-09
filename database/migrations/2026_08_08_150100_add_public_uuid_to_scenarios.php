<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenarios', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique();
        });

        DB::table('scenarios')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(200, function ($scenarios): void {
                foreach ($scenarios as $scenario) {
                    DB::table('scenarios')
                        ->where('id', $scenario->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('scenarios', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
