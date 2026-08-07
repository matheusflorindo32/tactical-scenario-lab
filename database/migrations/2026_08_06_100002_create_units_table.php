<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * units — subdivisões hierárquicas de uma organização.
 *
 * O catálogo contempla estruturas civis, acadêmicas, clínicas, empresariais
 * e militares sem amarrar a aplicação a uma única instituição.
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
                'headquarters',
                'regional',
                'department',
                'division',
                'battalion',
                'company',
                'platoon',
                'station',
                'base',
                'school',
                'clinic',
                'sector',
                'team',
                'branch',
                'other',
            ])->default('other');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'parent_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
