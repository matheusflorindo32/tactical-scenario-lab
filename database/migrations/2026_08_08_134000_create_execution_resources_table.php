<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_resources', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('scenario_execution_id')->constrained('scenario_executions')->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('planned_quantity')->default(1);
            $table->unsignedInteger('available_quantity')->default(1);
            $table->unsignedInteger('used_quantity')->default(0);
            $table->enum('status', ['available', 'unavailable', 'depleted'])->default('available');
            $table->timestamps();

            $table->unique(['scenario_execution_id', 'name']);
            $table->index(['scenario_execution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_resources');
    }
};
