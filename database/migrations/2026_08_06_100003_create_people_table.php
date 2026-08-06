<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * people — TODA pessoa (instrutor, aluno, avaliador, vítima simulada,
 * figurante, ator, apoio, observador). Papel é sempre CONTEXTUAL
 * (person_roles / organization_memberships / enrollments em fases futuras),
 * NUNCA campo fixo em people.
 *
 * Regra invariante: nada aqui bloqueia por documento faltando. Cadastro
 * simplificado só precisa de display_name — status arranca em 'incomplete'.
 *
 * Ver docs/EXPANSION_PLAN.md §3.1 e §5.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('people')) {
            return;
        }

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('display_name', 120);
            $table->string('social_name', 120)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->enum('status', ['active', 'incomplete', 'inactive', 'merged'])
                ->default('incomplete');
            // Quando status = merged, aponta para o registro canônico.
            $table->foreignId('merged_into')
                ->nullable()
                ->constrained('people')
                ->nullOnDelete();
            // Autor do cadastro. FK a users vira em Fase 2.2 (quando existir).
            // Deixamos como unsignedBigInteger nullable para evitar acoplamento agora.
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('display_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
