<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_organization_accesses', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('granted_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('user_organization_accesses', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
