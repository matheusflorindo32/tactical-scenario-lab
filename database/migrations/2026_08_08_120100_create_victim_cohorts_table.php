<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('victim_cohorts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('scenario_version_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->unsignedBigInteger('quantity');
            $table->json('profile')->nullable();
            $table->string('triage_category', 30)->nullable();
            $table->json('characteristics')->nullable();
            $table->timestamps();

            $table->index(['scenario_version_id', 'triage_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('victim_cohorts');
    }
};
