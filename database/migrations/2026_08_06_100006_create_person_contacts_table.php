<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * person_contacts — canais de comunicação da pessoa (e-mail, telefone,
 * contato de emergência). Separado de person_identifiers porque a natureza,
 * a máscara e a retenção são diferentes.
 *
 * SQLite não suporta índice único parcial; deduplicação de type=email/phone
 * dentro de uma organização é validada no controller (não no schema) para
 * não engessar variações legítimas (ex.: outro/emergency podem repetir).
 *
 * Ver docs/EXPANSION_PLAN.md §3.2 e §5.3.
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
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->enum('type', ['email', 'phone', 'emergency', 'other']);
            $table->string('value', 160);
            $table->string('value_normalized', 160);
            $table->string('label', 60)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'type', 'is_primary']);
            $table->index(['type', 'value_normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_contacts');
    }
};
