<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_teams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('scenario_execution_id')->constrained('scenario_executions')->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('description', 500)->nullable();
            $table->timestamps();

            $table->index(['scenario_execution_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_teams');
    }
};
