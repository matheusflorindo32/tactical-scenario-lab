<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Papel contextual da pessoa dentro de uma organização.
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
            $table->uuid('uuid')->unique();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->enum('role', [
                'admin_tma',
                'manager_org',
                'coordinator',
                'instructor',
                'evaluator',
                'student',
                'support',
                'auditor',
                'viewer',
            ]);
            $table->json('abilities')->nullable();
            $table->timestamp('granted_at')->useCurrent();
            $table->unsignedBigInteger('granted_by_user_id')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['person_id', 'organization_id', 'role'], 'person_role_lookup');
            $table->index(['organization_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_roles');
    }
};
