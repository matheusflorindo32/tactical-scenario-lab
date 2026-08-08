<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_assessments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('scenario_execution_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('source', 16)->default('m4');
            $table->string('status', 16)->default('draft');
            $table->decimal('pass_threshold', 5, 2)->nullable();
            $table->decimal('base_score', 5, 2)->nullable();
            $table->decimal('penalty_points', 6, 2)->nullable();
            $table->smallInteger('evaluator_adjustment')->default(0);
            $table->text('adjustment_justification')->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('result', 16)->nullable();
            $table->boolean('automatic_fail')->default(false);
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('legacy_imported_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_assessments');
    }
};
