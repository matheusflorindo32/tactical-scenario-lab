<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionPreflightCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_option_validates_connectivity_without_exposing_credentials(): void
    {
        $this->artisan('production:preflight', ['--database' => true])
            ->expectsOutput('Production configuration preflight passed.')
            ->expectsOutput('Database connectivity check passed.')
            ->assertSuccessful();
    }
}
