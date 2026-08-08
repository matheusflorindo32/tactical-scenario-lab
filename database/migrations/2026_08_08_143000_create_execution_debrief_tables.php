<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_debriefs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('execution_assessment_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('debrief_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('execution_debrief_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32);
            $table->text('content');
            $table->unsignedInteger('position')->default(1);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('action_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('execution_debrief_id')->constrained()->cascadeOnDelete();
            $table->text('action');
            $table->foreignId('responsible_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('responsible_label', 200)->nullable();
            $table->date('due_date');
            $table->string('status', 24)->default('open');
            $table->text('notes')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('status_changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_items');
        Schema::dropIfExists('debrief_entries');
        Schema::dropIfExists('execution_debriefs');
    }
};
