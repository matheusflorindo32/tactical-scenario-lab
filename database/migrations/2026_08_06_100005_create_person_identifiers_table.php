<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos e códigos operacionais da pessoa.
 *
 * O valor original é criptografado. A busca exata usa uma impressão digital
 * HMAC e a interface recebe apenas o valor mascarado. Não há restrição única
 * rígida: possíveis duplicidades geram alerta e decisão humana.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('person_identifiers')) {
            return;
        }

        Schema::create('person_identifiers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->enum('type', [
                'cpf',
                'rg',
                'id_funcional',
                'matricula',
                'passaporte',
                'registro_profissional',
                'temp_code',
                'qr',
                'other',
            ]);
            $table->text('value_encrypted');
            $table->char('value_fingerprint', 64);
            $table->string('masked_value', 160);
            $table->string('issuer', 60)->nullable();
            $table->char('country', 2)->nullable();
            $table->char('state', 2)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'type', 'value_fingerprint'], 'person_identifier_lookup');
            $table->index(['person_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_identifiers');
    }
};
