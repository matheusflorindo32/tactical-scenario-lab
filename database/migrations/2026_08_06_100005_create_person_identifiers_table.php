<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * person_identifiers — documentos e códigos operacionais (CPF, RG,
 * matrícula, passaporte, registro profissional, temp_code, QR).
 *
 * `value_normalized` é a versão canonizada (só dígitos para CPF/telefone,
 * lowercase p/ outros) — usada para busca exata e detecção de duplicidade.
 *
 * Índice único (type, value_normalized, organization_id) previne o MESMO
 * documento em cadastros distintos DENTRO da mesma organização; entre
 * organizações a mesma pessoa pode ter cadastros distintos até que um
 * curador faça mesclagem.
 *
 * Ver docs/EXPANSION_PLAN.md §3.2 e §5.2.
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
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->enum('type', [
                'cpf', 'rg', 'id_funcional', 'matricula', 'passaporte',
                'registro_profissional', 'temp_code', 'qr', 'other',
            ]);
            $table->string('value', 60);
            $table->string('value_normalized', 60);
            $table->string('issuer', 60)->nullable();
            $table->char('country', 2)->nullable();
            $table->char('state', 2)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Não permite mesmo documento repetido dentro da MESMA organização.
            $table->unique(
                ['type', 'value_normalized', 'organization_id'],
                'person_identifiers_type_value_org_unique',
            );
            $table->index(['person_id', 'is_primary']);
            $table->index('value_normalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_identifiers');
    }
};
