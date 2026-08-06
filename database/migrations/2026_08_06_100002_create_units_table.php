<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * units — subdivisões de uma organização (batalhão, companhia, base, escola,
 * regional, filial). Hierárquicas via parent_unit_id.
 *
 * Ver docs/EXPANSION_PLAN.md §14 e §17.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('units')) {
            return;
        }

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
            $table->foreignId('parent_unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();
            $table->string('name', 160);
            $table->enum('kind', [
                'battalion', 'company', 'base', 'school',
                'sector', 'team', 'regional', 'branch', 'other',
            ])->default('other');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
