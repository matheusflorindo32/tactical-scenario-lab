<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * audit_logs — trilha polimórfica de ações sensíveis, escopada por
 * organização. Nunca contém CPF/RG em texto claro no payload; usa hash ou
 * máscara quando relevante.
 *
 * `organization_id` nullable para ações do admin_tma cruzando orgs (com
 * gatilho auditado).
 *
 * Ver docs/EXPANSION_PLAN.md §8 e §14.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained('organizations')
                ->nullOnDelete();
            // Ator polimórfico simples: 'user' | 'system' + id opcional.
            $table->string('actor_type', 40)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            // ex.: 'person.created', 'person.merged', 'pii.revealed'
            $table->string('action', 120);
            // Sujeito polimórfico simples.
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_at')->useCurrent();

            $table->index(['organization_id', 'action']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('logged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
