<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Person;
use App\Models\OrganizationMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_screen_is_available(): void
    {
        $organization = Organization::create([
            'name' => 'Organização Alfa',
            'kind' => 'military',
            'status' => 'active',
        ]);

        $this->get(route('organizations.edit', $organization))
            ->assertOk()
            ->assertSee('Editar organização')
            ->assertSee('Organização Alfa');
    }

    public function test_organization_can_be_updated_without_copying_free_text_to_audit(): void
    {
        $organization = Organization::create([
            'name' => 'Organização Alfa',
            'kind' => 'military',
            'status' => 'active',
            'notes' => 'Texto anterior',
        ]);

        $response = $this->put(route('organizations.update', $organization), [
            'name' => 'Organização Bravo',
            'kind' => 'school',
            'status' => 'active',
            'notes' => 'Informação institucional reservada',
        ]);

        $response->assertRedirect(route('organizations.show', $organization));

        $organization->refresh();
        $this->assertSame('Organização Bravo', $organization->name);
        $this->assertSame('school', $organization->kind);

        $log = AuditLog::query()->where('action', 'organization.updated')->firstOrFail();
        $payload = json_encode($log->payload);

        $this->assertStringNotContainsString('Organização Bravo', $payload);
        $this->assertStringNotContainsString('Informação institucional reservada', $payload);
        $this->assertSame('military', $log->payload['previous_kind']);
        $this->assertSame('school', $log->payload['current_kind']);
    }

    public function test_inactivation_preserves_memberships_and_is_idempotent(): void
    {
        $organization = Organization::create([
            'name' => 'Organização Alfa',
            'kind' => 'military',
            'status' => 'active',
        ]);
        $person = Person::create(['display_name' => 'Pessoa Alfa']);
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $this->patch(route('organizations.deactivate', $organization))
            ->assertRedirect(route('organizations.show', $organization));
        $this->patch(route('organizations.deactivate', $organization))
            ->assertRedirect(route('organizations.show', $organization));

        $this->assertSame('inactive', $organization->fresh()->status);
        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organization->id,
            'person_id' => $person->id,
        ]);
        $this->assertSame(
            1,
            AuditLog::query()->where('action', 'organization.deactivated')->count(),
        );
    }
}
