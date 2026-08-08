<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenario_victims', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('scenario_version_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->json('profile')->nullable();
            $table->json('injuries')->nullable();
            $table->json('initial_state')->nullable();
            $table->string('expected_priority', 30)->nullable();
            $table->timestamps();

            $table->unique(['scenario_version_id', 'code']);
            $table->index(['scenario_version_id', 'expected_priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_victims');
    }
};
