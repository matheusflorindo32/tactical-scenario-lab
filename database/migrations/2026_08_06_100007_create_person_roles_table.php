<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * person_roles — papel contextual da pessoa DENTRO de uma organização.
 * NÃO é o "papel único permanente" (esse conceito não existe no plano).
 *
 * Abilities extras (json) permitem compor permissões granulares como
 * `pii_reveal`, `export_reports`, `manage_protocols`, `merge_people`
 * sem inflar o enum de roles.
 *
 * Ver docs/EXPANSION_PLAN.md §3.3 e §9.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('person_roles')) {
            return;
        }

        Schema::create('person_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->enum('role', [
                'admin_tma', 'manager_org', 'coordinator', 'instructor',
                'evaluator', 'student', 'support', 'auditor', 'viewer',
            ]);
            $table->json('abilities')->nullable();
            $table->timestamp('granted_at')->useCurrent();
            $table->unsignedBigInteger('granted_by_user_id')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['person_id', 'organization_id', 'role'],
                'person_roles_person_org_role_unique',
            );
            $table->index(['organization_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_roles');
    }
};
