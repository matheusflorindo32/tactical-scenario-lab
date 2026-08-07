<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_edit_form_is_available(): void
    {
        $person = Person::create(['display_name' => 'Operador Alfa']);

        $this->get(route('people.edit', $person))
            ->assertOk()
            ->assertSee('Editar cadastro')
            ->assertSee('Operador Alfa');
    }

    public function test_profile_exposes_protected_management_actions(): void
    {
        [, $person] = $this->context();

        $this->get(route('people.show', $person))
            ->assertOk()
            ->assertSee(route('people.edit', $person), false)
            ->assertSee(route('people.identifiers.create', $person), false)
            ->assertSee(route('people.contacts.create', $person), false)
            ->assertSee(route('people.memberships.create', $person), false)
            ->assertSee(route('people.roles.create', $person), false)
            ->assertSee(route('people.deactivate', $person), false);
    }

    public function test_person_general_data_can_be_updated_and_is_audited_without_values(): void
    {
        [$organization, $person] = $this->context();

        $response = $this->put(route('people.update', $person), [
            'display_name' => 'Operador Bravo',
            'social_name' => 'Bravo',
            'birth_date' => '1992-02-14',
            'status' => 'active',
            'notes' => 'Observação clínica reservada.',
        ]);

        $response->assertRedirect(route('people.show', $person));

        $person->refresh();
        $this->assertSame('Operador Bravo', $person->display_name);
        $this->assertSame('Bravo', $person->social_name);
        $this->assertSame('active', $person->status);

        $log = AuditLog::query()->where('action', 'person.updated')->firstOrFail();
        $this->assertSame($organization->id, $log->organization_id);
        $this->assertSame($person->id, $log->subject_id);
        $this->assertContains('display_name', $log->payload['changed_fields']);
        $this->assertContains('status', $log->payload['changed_fields']);
        $this->assertStringNotContainsString('Operador Bravo', json_encode($log->payload));
        $this->assertStringNotContainsString('Observação clínica reservada', json_encode($log->payload));
    }

    public function test_future_birth_date_is_rejected(): void
    {
        [, $person] = $this->context();

        $this->from(route('people.edit', $person))
            ->put(route('people.update', $person), [
                'display_name' => 'Operador Alfa',
                'birth_date' => now()->addDay()->toDateString(),
                'status' => 'incomplete',
            ])
            ->assertRedirect(route('people.edit', $person))
            ->assertSessionHasErrors('birth_date');
    }

    public function test_person_can_be_inactivated_without_deleting_history(): void
    {
        [$organization, $person] = $this->context('active');
        $membershipId = $person->memberships()->value('id');

        $this->patch(route('people.deactivate', $person))
            ->assertRedirect(route('people.show', $person));

        $this->assertSame('inactive', $person->refresh()->status);
        $this->assertDatabaseHas('organization_memberships', ['id' => $membershipId]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organization->id,
            'action' => 'person.deactivated',
            'subject_id' => $person->id,
        ]);
    }

    public function test_repeated_inactivation_is_idempotent(): void
    {
        [, $person] = $this->context('active');

        $this->patch(route('people.deactivate', $person));
        $this->patch(route('people.deactivate', $person));

        $this->assertSame(1, AuditLog::query()
            ->where('action', 'person.deactivated')
            ->where('subject_id', $person->id)
            ->count());
    }

    private function context(string $status = 'incomplete'): array
    {
        $organization = Organization::create([
            'name' => 'Instituição Diamante',
            'type' => 'academy',
            'status' => 'active',
        ]);

        $person = Person::create([
            'display_name' => 'Operador Alfa',
            'status' => $status,
        ]);

        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        return [$organization, $person];
    }
}
