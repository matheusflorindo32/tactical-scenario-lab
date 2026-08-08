<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_screen_is_available(): void
    {
        [$organization, $unit] = $this->unitContext();

        $this->get(route('units.edit', $unit))
            ->assertOk()
            ->assertSee('Editar unidade')
            ->assertSee($organization->name)
            ->assertSee($unit->name);
    }

    public function test_unit_can_be_updated_without_copying_free_text_to_audit(): void
    {
        [, $unit] = $this->unitContext();

        $this->put(route('units.update', $unit), [
            'name' => 'Unidade Bravo',
            'kind' => 'department',
            'status' => 'active',
            'notes' => 'Informação operacional reservada',
        ])->assertRedirect(route('organizations.show', $unit->organization));

        $unit->refresh();
        $this->assertSame('Unidade Bravo', $unit->name);
        $this->assertSame('department', $unit->kind);

        $log = AuditLog::query()->where('action', 'unit.updated')->firstOrFail();
        $payload = json_encode($log->payload);

        $this->assertStringNotContainsString('Unidade Bravo', $payload);
        $this->assertStringNotContainsString('Informação operacional reservada', $payload);
        $this->assertSame('company', $log->payload['previous_kind']);
        $this->assertSame('department', $log->payload['current_kind']);
    }

    public function test_unit_cannot_be_its_own_parent_or_create_hierarchy_cycle(): void
    {
        [$organization, $unit] = $this->unitContext();
        $child = Unit::create([
            'organization_id' => $organization->id,
            'parent_unit_id' => $unit->id,
            'name' => 'Unidade Filha',
            'kind' => 'platoon',
            'status' => 'active',
        ]);

        $this->from(route('units.edit', $unit))
            ->put(route('units.update', $unit), [
                'name' => $unit->name,
                'kind' => $unit->kind,
                'status' => $unit->status,
                'parent_unit_id' => $unit->id,
            ])
            ->assertSessionHasErrors('parent_unit_id');

        $this->from(route('units.edit', $unit))
            ->put(route('units.update', $unit), [
                'name' => $unit->name,
                'kind' => $unit->kind,
                'status' => $unit->status,
                'parent_unit_id' => $child->id,
            ])
            ->assertSessionHasErrors('parent_unit_id');
    }

    public function test_inactivation_preserves_memberships_and_is_idempotent(): void
    {
        [$organization, $unit] = $this->unitContext();
        $person = Person::create(['display_name' => 'Pessoa Alfa']);
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $this->patch(route('units.deactivate', $unit))
            ->assertRedirect(route('organizations.show', $organization));
        $this->patch(route('units.deactivate', $unit))
            ->assertRedirect(route('organizations.show', $organization));

        $this->assertSame('inactive', $unit->fresh()->status);
        $this->assertDatabaseHas('organization_memberships', [
            'person_id' => $person->id,
            'unit_id' => $unit->id,
        ]);
        $this->assertSame(1, AuditLog::query()->where('action', 'unit.deactivated')->count());
    }

    private function unitContext(): array
    {
        $organization = Organization::create([
            'name' => 'Organização Alfa',
            'kind' => 'military',
            'status' => 'active',
        ]);
        $unit = Unit::create([
            'organization_id' => $organization->id,
            'name' => 'Unidade Alfa',
            'kind' => 'company',
            'status' => 'active',
        ]);

        return [$organization, $unit];
    }
}
