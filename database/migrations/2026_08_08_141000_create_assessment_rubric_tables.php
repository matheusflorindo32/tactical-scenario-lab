<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_criteria', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('execution_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80)->nullable();
            $table->string('label', 200);
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2);
            $table->decimal('score', 5, 2)->nullable();
            $table->text('evaluator_notes')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->index(['execution_assessment_id', 'position']);
        });

        Schema::create('assessment_evidence', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('assessment_criterion_id')->constrained('assessment_criteria')->cascadeOnDelete();
            $table->foreignId('execution_event_id')->nullable()->constrained('execution_events')->nullOnDelete();
            $table->text('statement');
            $table->timestamp('observed_at');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_evidence');
        Schema::dropIfExists('assessment_criteria');
    }
};
