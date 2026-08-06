<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canais de comunicação da pessoa.
 *
 * O valor original é criptografado. Busca e alerta de duplicidade usam HMAC;
 * listagens recebem somente a versão mascarada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('person_contacts')) {
            return;
        }

        Schema::create('person_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->enum('type', ['email', 'phone', 'whatsapp', 'emergency', 'other']);
            $table->text('value_encrypted');
            $table->char('value_fingerprint', 64);
            $table->string('masked_value', 160);
            $table->string('label', 60)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['person_id', 'type', 'is_primary']);
            $table->index(['organization_id', 'type', 'value_fingerprint'], 'person_contact_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_contacts');
    }
};
