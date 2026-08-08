<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_organization_and_unit_workflows(): void
    {
        $organization = Organization::create([
            'name' => 'Organização Protegida',
            'kind' => 'military',
            'status' => 'active',
        ]);
        $unit = Unit::create([
            'organization_id' => $organization->id,
            'name' => 'Unidade Protegida',
            'kind' => 'company',
            'status' => 'active',
        ]);

        $this->get(route('organizations.index'))->assertRedirect(route('login'));
        $this->get(route('organizations.create'))->assertRedirect(route('login'));
        $this->get(route('organizations.show', $organization))->assertRedirect(route('login'));
        $this->get(route('organizations.edit', $organization))->assertRedirect(route('login'));
        $this->post(route('organizations.store'), [])->assertRedirect(route('login'));
        $this->put(route('organizations.update', $organization), [])->assertRedirect(route('login'));
        $this->patch(route('organizations.deactivate', $organization))->assertRedirect(route('login'));

        $this->get(route('organizations.units.create', $organization))->assertRedirect(route('login'));
        $this->get(route('units.edit', $unit))->assertRedirect(route('login'));
        $this->post(route('units.store'), [])->assertRedirect(route('login'));
        $this->put(route('units.update', $unit), [])->assertRedirect(route('login'));
        $this->patch(route('units.deactivate', $unit))->assertRedirect(route('login'));
    }

    public function test_authenticated_active_user_can_open_organization_and_unit_workflows(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $organization = Organization::create([
            'name' => 'Organização Diamante',
            'kind' => 'military',
            'status' => 'active',
        ]);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'manager_org',
            'abilities' => ['people.view'],
            'granted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);

        $this->get(route('organizations.index'))->assertOk();
        $this->get(route('organizations.show', $organization))->assertOk();
        $this->get(route('organizations.units.create', $organization))->assertOk();
    }
}
