<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Person;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicUuidFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_keeps_bigint_primary_key_and_receives_public_uuid(): void
    {
        $organization = Organization::create([
            'name' => 'Tactical Medicine Academy',
        ]);

        $this->assertIsInt($organization->id);
        $this->assertTrue(Str::isUuid($organization->uuid));
        $this->assertSame('uuid', $organization->getRouteKeyName());
    }

    public function test_unit_keeps_bigint_primary_key_and_relationship_uses_internal_id(): void
    {
        $organization = Organization::create([
            'name' => 'Tactical Medicine Academy',
        ]);

        $unit = Unit::create([
            'organization_id' => $organization->id,
            'name' => 'Centro de Treinamento',
        ]);

        $this->assertIsInt($unit->id);
        $this->assertTrue(Str::isUuid($unit->uuid));
        $this->assertSame($organization->id, $unit->organization_id);
        $this->assertTrue($unit->organization->is($organization));
    }

    public function test_person_can_be_created_without_documents_or_contacts(): void
    {
        $person = Person::create([
            'display_name' => 'Pessoa de Teste',
        ]);

        $this->assertIsInt($person->id);
        $this->assertTrue(Str::isUuid($person->uuid));
        $this->assertSame('incomplete', $person->status);
        $this->assertSame('uuid', $person->getRouteKeyName());
        $this->assertDatabaseHas('people', [
            'id' => $person->id,
            'display_name' => 'Pessoa de Teste',
        ]);
    }

    public function test_public_uuid_is_unique(): void
    {
        $first = Person::create(['display_name' => 'Primeira Pessoa']);
        $second = Person::create(['display_name' => 'Segunda Pessoa']);

        $this->assertNotSame($first->uuid, $second->uuid);
    }
}
