<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\PersonRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationMembershipClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_membership_can_be_closed_without_deleting_history(): void
    {
        [$organization, $person, $membership] = $this->context();
        $role = PersonRole::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'role' => 'instructor',
            'granted_at' => now(),
        ]);

        $this->patch(route('people.memberships.close', [$person, $membership]))
            ->assertRedirect(route('people.show', $person));

        $membership->refresh();
        $this->assertSame('inactive', $membership->status);
        $this->assertNotNull($membership->ended_at);
        $this->assertDatabaseHas('organization_memberships', ['id' => $membership->id]);
        $this->assertNotNull($role->fresh()->revoked_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'organization_membership.closed',
            'organization_id' => $organization->id,
            'subject_id' => $membership->id,
        ]);
    }

    public function test_roles_remain_active_when_another_membership_in_same_organization_exists(): void
    {
        [$organization, $person, $membership] = $this->context();
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'position' => 'Segundo vínculo',
            'status' => 'active',
        ]);
        $role = PersonRole::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'role' => 'instructor',
            'granted_at' => now(),
        ]);

        $this->patch(route('people.memberships.close', [$person, $membership]));

        $this->assertNull($role->fresh()->revoked_at);
    }

    public function test_closure_is_idempotent_and_cannot_target_another_person_membership(): void
    {
        [$organization, $person, $membership] = $this->context();

        $this->patch(route('people.memberships.close', [$person, $membership]));
        $this->patch(route('people.memberships.close', [$person, $membership]));

        $this->assertSame(1, AuditLog::query()
            ->where('action', 'organization_membership.closed')
            ->where('subject_id', $membership->id)
            ->count());

        $otherPerson = Person::create(['display_name' => 'Outra pessoa']);

        $this->patch(route('people.memberships.close', [$otherPerson, $membership]))
            ->assertNotFound();

        $this->assertSame($organization->id, $membership->organization_id);
    }

    private function context(): array
    {
        $organization = Organization::create([
            'name' => 'Organização Diamante',
            'kind' => 'military',
            'status' => 'active',
        ]);
        $person = Person::create([
            'display_name' => 'Operador Alfa',
            'status' => 'active',
        ]);
        $membership = OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'position' => 'Instrutor',
            'status' => 'active',
            'started_at' => now()->subMonth()->toDateString(),
        ]);

        return [$organization, $person, $membership];
    }
}
