<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_injects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('scenario_execution_id')->constrained('scenario_executions')->cascadeOnDelete();
            $table->string('label', 150);
            $table->text('content');
            $table->unsignedInteger('planned_offset_seconds')->nullable();
            $table->enum('status', ['planned', 'delivered', 'cancelled'])->default('planned');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['scenario_execution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_injects');
    }
};
