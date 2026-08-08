<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Person;
use App\Models\PersonContact;
use App\Models\PersonIdentifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProtectedPersonalDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_cpf_is_encrypted_fingerprinted_and_masked(): void
    {
        [$organization, $person] = $this->context();

        $identifier = PersonIdentifier::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'type' => 'cpf',
            'value' => '123.456.789-09',
        ]);

        $stored = DB::table('person_identifiers')->where('id', $identifier->id)->first();

        $this->assertNotSame('123.456.789-09', $stored->value_encrypted);
        $this->assertNotSame('12345678909', $stored->value_fingerprint);
        $this->assertSame('***.***.***-09', $stored->masked_value);
        $this->assertSame('123.456.789-09', $identifier->fresh()->value);
        $this->assertArrayNotHasKey('value_encrypted', $identifier->fresh()->toArray());
        $this->assertArrayNotHasKey('value_fingerprint', $identifier->fresh()->toArray());
    }

    public function test_equivalent_cpf_formats_produce_the_same_fingerprint(): void
    {
        $formatted = PersonIdentifier::fingerprintFor('cpf', '123.456.789-09');
        $digits = PersonIdentifier::fingerprintFor('cpf', '12345678909');

        $this->assertSame($formatted, $digits);
    }

    public function test_email_is_encrypted_and_masked(): void
    {
        [$organization, $person] = $this->context();

        $contact = PersonContact::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'type' => 'email',
            'value' => 'Matheus.Exemplo@Email.com',
        ]);

        $stored = DB::table('person_contacts')->where('id', $contact->id)->first();

        $this->assertNotSame('Matheus.Exemplo@Email.com', $stored->value_encrypted);
        $this->assertSame('ma*************@email.com', $stored->masked_value);
        $this->assertSame('Matheus.Exemplo@Email.com', $contact->fresh()->value);
        $this->assertArrayNotHasKey('value_encrypted', $contact->fresh()->toArray());
        $this->assertArrayNotHasKey('value_fingerprint', $contact->fresh()->toArray());
    }

    public function test_duplicate_fingerprint_does_not_block_a_supervised_duplicate(): void
    {
        [$organization, $firstPerson] = $this->context();
        $secondPerson = Person::create(['display_name' => 'Segundo cadastro supervisionado']);

        foreach ([$firstPerson, $secondPerson] as $person) {
            PersonIdentifier::create([
                'person_id' => $person->id,
                'organization_id' => $organization->id,
                'type' => 'matricula',
                'value' => 'ABC-123',
            ]);
        }

        $fingerprint = PersonIdentifier::fingerprintFor('matricula', 'abc-123');

        $this->assertSame(
            2,
            PersonIdentifier::query()
                ->where('organization_id', $organization->id)
                ->where('type', 'matricula')
                ->where('value_fingerprint', $fingerprint)
                ->count(),
        );
    }

    /**
     * @return array{Organization, Person}
     */
    private function context(): array
    {
        $organization = Organization::create(['name' => 'Tactical Medicine Academy']);
        $person = Person::create(['display_name' => 'Pessoa de Teste']);

        return [$organization, $person];
    }
}
