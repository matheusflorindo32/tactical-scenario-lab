<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_participants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('scenario_execution_id')->constrained('scenario_executions')->cascadeOnDelete();
            $table->foreignId('execution_team_id')->nullable()->constrained('execution_teams')->nullOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->string('role', 80)->nullable();
            $table->timestamps();

            $table->unique(['scenario_execution_id', 'person_id']);
            $table->index(['scenario_execution_id', 'execution_team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_participants');
    }
};
