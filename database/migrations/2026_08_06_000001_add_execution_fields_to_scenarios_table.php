<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos para o ciclo de vida completo do cenário:
 *  - observed_critical_errors: erros REALMENTE cometidos na execução
 *    (o campo original `critical_errors` continua sendo o catálogo
 *    gerado pelo ScenarioGenerator, ou seja, o que MONITORAR).
 *  - started_at / completed_at: timestamps do fluxo run → complete.
 *
 * Migration incremental: não recria a tabela nem altera dados
 * existentes. Compatível com SQLite (driver padrão do MVP).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            if (! Schema::hasColumn('scenarios', 'observed_critical_errors')) {
                $table->json('observed_critical_errors')->nullable()->after('critical_errors');
            }
            if (! Schema::hasColumn('scenarios', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('debrief_notes');
            }
            if (! Schema::hasColumn('scenarios', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scenarios', function (Blueprint $table) {
            foreach (['observed_critical_errors', 'started_at', 'completed_at'] as $col) {
                if (Schema::hasColumn('scenarios', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
