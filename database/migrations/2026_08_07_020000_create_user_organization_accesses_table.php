<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('id')->constrained('people')->nullOnDelete();
            $table->string('status', 30)->default('active')->after('password')->index();
        });

        Schema::create('user_organization_accesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50);
            $table->json('abilities')->nullable();
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'organization_id', 'role'], 'user_org_role_unique');
            $table->index(['user_id', 'organization_id', 'revoked_at'], 'user_org_active_access_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_organization_accesses');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
            $table->dropColumn('status');
        });
    }
};
