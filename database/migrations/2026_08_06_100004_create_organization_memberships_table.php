<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * organization_memberships — vínculo pessoa ↔ organização, com período e
 * (opcional) unidade. Não impede múltiplas memberships por pessoa/org
 * (transições históricas). Ver docs/EXPANSION_PLAN.md §3.1 e §3.4.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('organization_memberships')) {
            return;
        }

        Schema::create('organization_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            // Cargo/posto/graduação livre — opcional por default.
            $table->string('position', 120)->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'organization_id']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_memberships');
    }
};
