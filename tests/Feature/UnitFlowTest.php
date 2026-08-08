<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_form_is_available_for_organization(): void
    {
        $organization = Organization::create(['name' => 'Tactical Medicine Academy']);

        $this->get(route('organizations.units.create', $organization))
            ->assertOk()
            ->assertSee('Nova unidade')
            ->assertSee($organization->name);
    }

    public function test_unit_can_be_created_without_parent(): void
    {
        $organization = Organization::create(['name' => 'Tactical Medicine Academy']);

        $response = $this->post(route('units.store'), [
            'organization_id' => $organization->id,
            'name' => 'Centro de Treinamento Operacional',
            'kind' => 'school',
            'status' => 'active',
        ]);

        $unit = Unit::firstOrFail();

        $response->assertRedirect(route('organizations.show', $organization));
        $this->assertSame($organization->id, $unit->organization_id);
        $this->assertNull($unit->parent_unit_id);
        $this->assertDatabaseHas('units', [
            'name' => 'Centro de Treinamento Operacional',
            'kind' => 'school',
            'status' => 'active',
        ]);
    }

    public function test_parent_unit_must_belong_to_same_organization(): void
    {
        $first = Organization::create(['name' => 'Primeira organização']);
        $second = Organization::create(['name' => 'Segunda organização']);
        $foreignParent = Unit::create([
            'organization_id' => $second->id,
            'name' => 'Unidade superior externa',
        ]);

        $this->post(route('units.store'), [
            'organization_id' => $first->id,
            'parent_unit_id' => $foreignParent->id,
            'name' => 'Unidade inválida',
            'kind' => 'department',
            'status' => 'active',
        ])->assertSessionHasErrors('parent_unit_id');

        $this->assertDatabaseCount('units', 1);
    }

    public function test_child_unit_can_reference_parent_from_same_organization(): void
    {
        $organization = Organization::create(['name' => 'Tactical Medicine Academy']);
        $parent = Unit::create([
            'organization_id' => $organization->id,
            'name' => 'Diretoria Operacional',
            'kind' => 'division',
        ]);

        $this->post(route('units.store'), [
            'organization_id' => $organization->id,
            'parent_unit_id' => $parent->id,
            'name' => 'Núcleo de Simulação',
            'kind' => 'department',
            'status' => 'active',
        ])->assertRedirect(route('organizations.show', $organization));

        $child = Unit::query()->where('name', 'Núcleo de Simulação')->firstOrFail();

        $this->assertSame($parent->id, $child->parent_unit_id);
        $this->assertTrue($child->parent->is($parent));
    }
}
