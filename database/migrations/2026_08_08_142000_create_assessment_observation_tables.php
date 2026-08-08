<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('critical_error_occurrences', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('execution_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('catalog_label_snapshot', 500);
            $table->string('rule', 24)->default('record');
            $table->decimal('penalty_points', 6, 2)->default(0);
            $table->foreignId('execution_event_id')->nullable()->constrained('execution_events')->nullOnDelete();
            $table->timestamp('observed_at');
            $table->text('notes')->nullable();
            $table->string('source', 16)->default('m4');
            $table->timestamps();

            $table->unique(
                ['execution_assessment_id', 'catalog_label_snapshot'],
                'assessment_critical_error_unique',
            );
        });

        Schema::create('key_time_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('execution_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('label', 200);
            $table->timestamp('occurred_at');
            $table->unsignedInteger('elapsed_seconds');
            $table->unsignedInteger('reference_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_time_records');
        Schema::dropIfExists('critical_error_occurrences');
    }
};
