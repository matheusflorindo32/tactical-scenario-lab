<?php

namespace Tests;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->legacyProtectedFlowNeedsAuthentication()) {
            $user = User::factory()->create(['status' => 'active']);
            $this->actingAs($user);

            Organization::created(function (Organization $organization) use ($user): void {
                UserOrganizationAccess::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'organization_id' => $organization->id,
                        'role' => 'legacy_test_manager',
                    ],
                    [
                        'abilities' => ['people.view', 'people.manage'],
                        'granted_at' => now(),
                    ],
                );

                if (! session()->has('active_organization_id')) {
                    session(['active_organization_id' => $organization->id]);
                }
            });
        }
    }

    private function legacyProtectedFlowNeedsAuthentication(): bool
    {
        return in_array(class_basename(static::class), [
            'InstitutionalAuditFlowTest',
            'OrganizationManagementFlowTest',
            'OrganizationMembershipClosureTest',
            'OrganizationMembershipFlowTest',
            'OrganizationPeopleFlowTest',
            'PeopleUniversalSearchTest',
            'PersonContactFlowTest',
            'PersonIdentifierFlowTest',
            'PersonManagementFlowTest',
            'PersonRoleFlowTest',
            'ScenarioFlowTest',
            'UnitFlowTest',
            'UnitManagementFlowTest',
        ], true);
    }
}
