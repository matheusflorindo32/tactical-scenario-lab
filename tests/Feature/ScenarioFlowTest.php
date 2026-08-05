<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScenarioFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenario_can_be_created(): void
    {
        $response = $this->post('/scenarios', [
            'environment' => 'Ambiente urbano', 'threat_level' => 'potencial',
            'casualties' => 1, 'mechanism' => 'Ferimento penetrante',
            'resources' => ['Kit IFAK', 'Rádio'],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('scenarios', ['environment' => 'Ambiente urbano', 'status' => 'draft']);
    }
}
