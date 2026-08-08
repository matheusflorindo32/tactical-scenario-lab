<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * organizations — instituições atendidas pela plataforma (TMA, corporações,
 * unidades militares, escolas, hospitais, empresas parceiras, etc.).
 *
 * Escopo obrigatório de todas as consultas de pessoas/contatos/documentos
 * na Fase 2.1. Ver docs/EXPANSION_PLAN.md §5.1 e §8.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('organizations')) {
            return;
        }

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 160);
            $table->enum('kind', [
                'tma', 'corporation', 'military', 'school', 'university',
                'prefecture', 'hospital', 'clinic', 'company', 'partner',
                'client', 'other',
            ])->default('other');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
