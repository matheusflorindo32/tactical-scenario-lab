<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Scenario;
use App\Models\Unit;
use App\Reporting\InstitutionalFilter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InstitutionalFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_default_period_is_last_ninety_calendar_days_and_client_organization_is_ignored(): void
    {
        CarbonImmutable::setTestNow('2026-08-08 12:00:00');

        $filter = InstitutionalFilter::fromRequest(
            Request::create('/dashboard', 'GET', ['organization_id' => 999]),
            12,
        );

        $this->assertSame(12, $filter->organizationId);
        $this->assertSame('2026-05-11', $filter->dateFrom->toDateString());
        $this->assertSame('2026-08-08', $filter->dateTo->toDateString());
    }

    public function test_period_over_366_inclusive_days_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        InstitutionalFilter::fromRequest(
            Request::create('/dashboard', 'GET', [
                'date_from' => '2025-01-01',
                'date_to' => '2026-01-02',
            ]),
            1,
        );
    }

    public function test_unit_and_scenario_filters_must_belong_to_active_organization(): void
    {
        $organization = Organization::create([
            'name' => 'Organização M5 Filtro',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $other = Organization::create([
            'name' => 'Organização M5 Externa',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $unit = Unit::create([
            'organization_id' => $organization->id,
            'name' => 'Unidade Filtro',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $scenario = $this->scenario($organization, 'Cenário Filtro');

        $filter = InstitutionalFilter::fromRequest(
            Request::create('/history/executions', 'GET', [
                'unit_uuid' => $unit->uuid,
                'scenario_uuid' => $scenario->uuid,
            ]),
            $organization->id,
        );

        $this->assertSame($unit->id, $filter->unitId);
        $this->assertSame($scenario->id, $filter->scenarioId);

        $foreignUnit = Unit::create([
            'organization_id' => $other->id,
            'name' => 'Unidade Externa',
            'kind' => 'company',
            'status' => 'active',
        ]);

        try {
            InstitutionalFilter::fromRequest(
                Request::create('/history/executions', 'GET', ['unit_uuid' => $foreignUnit->uuid]),
                $organization->id,
            );
            $this->fail('Foreign unit filter was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('unit_uuid', $exception->errors());
        }
    }

    private function scenario(Organization $organization, string $title): Scenario
    {
        return Scenario::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'environment' => 'Área controlada',
            'threat_level' => 'controlada',
            'casualties' => 1,
            'estimated_casualty_count' => 1,
            'mechanism' => 'Simulação',
            'resources' => ['Rádio'],
            'learning_objectives' => ['Coordenação'],
            'expected_actions' => ['Estabelecer comando'],
            'critical_errors' => ['Falha'],
            'status' => 'draft',
        ]);
    }
}
