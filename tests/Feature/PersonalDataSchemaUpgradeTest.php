<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PersonalDataSchemaUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_data_tables_use_only_protected_storage_columns(): void
    {
        foreach (['person_identifiers', 'person_contacts'] as $table) {
            $this->assertTrue(Schema::hasColumns($table, [
                'uuid',
                'value_encrypted',
                'value_fingerprint',
                'masked_value',
                'deleted_at',
            ]));

            $this->assertFalse(
                Schema::hasColumn($table, 'value'),
                "A tabela {$table} não pode manter PII em texto simples.",
            );
        }
    }
}
