<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('scenario_execution_id')->constrained('scenario_executions')->cascadeOnDelete();
            $table->foreignId('execution_team_id')->nullable()->constrained('execution_teams')->nullOnDelete();
            $table->foreignId('execution_participant_id')->nullable()->constrained('execution_participants')->nullOnDelete();
            $table->enum('kind', ['observation', 'action', 'intervention', 'system', 'inject', 'resource']);
            $table->timestamp('occurred_at');
            $table->string('summary', 500);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['scenario_execution_id', 'occurred_at']);
            $table->index(['scenario_execution_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_events');
    }
};
