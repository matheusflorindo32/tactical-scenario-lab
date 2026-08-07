<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Feature\ScenarioFlowTest;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (static::class === ScenarioFlowTest::class) {
            $this->actingAs(User::factory()->create(['status' => 'active']));
        }
    }
}
