<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->legacyProtectedFlowNeedsAuthentication()) {
            $this->actingAs(User::factory()->create(['status' => 'active']));
        }
    }

    private function legacyProtectedFlowNeedsAuthentication(): bool
    {
        return in_array(class_basename(static::class), [
            'InstitutionalAuditFlowTest',
            'OrganizationManagementFlowTest',
            'OrganizationPeopleFlowTest',
            'ScenarioFlowTest',
            'UnitFlowTest',
            'UnitManagementFlowTest',
        ], true);
    }
}
