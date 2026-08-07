<?php

use App\Support\Normalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Atualiza instalações que já executaram versões antigas das tabelas de PII.
 *
 * As migrations de criação representam o estado ideal para instalações novas.
 * Esta migration incremental protege bancos existentes sem depender de editar
 * o histórico já executado: adiciona a estrutura segura, migra eventual coluna
 * `value` em texto simples e, por fim, remove essa coluna legada.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->upgradeTable('person_identifiers', 'identifier');
        $this->upgradeTable('person_contacts', 'contact');
    }

    public function down(): void
    {
        // Migração de segurança deliberadamente irreversível: não recriamos
        // colunas de PII em texto simples durante rollback.
    }

    private function upgradeTable(string $table, string $domain): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $addedFingerprint = false;

        Schema::table($table, function (Blueprint $blueprint) use ($table, &$addedFingerprint): void {
            if (! Schema::hasColumn($table, 'uuid')) {
                $blueprint->uuid('uuid')->nullable();
            }

            if (! Schema::hasColumn($table, 'value_encrypted')) {
                $blueprint->text('value_encrypted')->nullable();
            }

            if (! Schema::hasColumn($table, 'value_fingerprint')) {
                $blueprint->char('value_fingerprint', 64)->nullable();
                $addedFingerprint = true;
            }

            if (! Schema::hasColumn($table, 'masked_value')) {
                $blueprint->string('masked_value', 160)->nullable();
            }

            if (! Schema::hasColumn($table, 'deleted_at')) {
                $blueprint->softDeletes();
            }
        });

        $hasLegacyValue = Schema::hasColumn($table, 'value');

        DB::table($table)
            ->select(['id', 'type', ...($hasLegacyValue ? ['value'] : [])])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $domain, $hasLegacyValue): void {
                foreach ($rows as $row) {
                    $updates = [];

                    if (blank(DB::table($table)->where('id', $row->id)->value('uuid'))) {
                        $updates['uuid'] = (string) Str::uuid();
                    }

                    if ($hasLegacyValue && filled($row->value)) {
                        $normalizedType = (string) $row->type;
                        $plainValue = (string) $row->value;

                        $updates['value_encrypted'] = Crypt::encryptString($plainValue);
                        $updates['value_fingerprint'] = Normalizer::fingerprint($domain, $normalizedType, $plainValue);
                        $updates['masked_value'] = $this->mask($domain, $normalizedType, $plainValue);
                    }

                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });

        if ($addedFingerprint) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $index = $table === 'person_identifiers'
                    ? 'person_identifier_lookup'
                    : 'person_contact_lookup';

                $blueprint->index(['organization_id', 'type', 'value_fingerprint'], $index);
            });
        }

        if ($hasLegacyValue) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('value');
            });
        }
    }

    private function mask(string $domain, string $type, string $value): string
    {
        if ($domain === 'contact') {
            return match ($type) {
                'email' => Normalizer::maskEmail($value),
                'phone', 'whatsapp', 'emergency' => Normalizer::maskPhone($value),
                default => Normalizer::maskGeneric($value),
            };
        }

        return $type === 'cpf'
            ? Normalizer::maskCpf($value)
            : Normalizer::maskGeneric($value);
    }
};
