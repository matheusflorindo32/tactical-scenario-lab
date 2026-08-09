<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M7DashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_dashboard_orders_work_by_operational_attention_without_new_metrics(): void
    {
        [$organization, $user] = $this->institutionalUser(AccessAbility::all());

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertOk();

        $html = $response->getContent();
        $priorities = [
            'data-attention-priority="running"',
            'data-attention-priority="overdue-actions"',
            'data-attention-priority="unassessed"',
            'data-attention-priority="draft-assessments"',
            'data-attention-priority="due-soon"',
            'data-attention-priority="recent-finalized"',
        ];

        $positions = array_map(fn (string $marker): int|false => strpos($html, $marker), $priorities);

        foreach ($positions as $index => $position) {
            $this->assertNotFalse($position, "Missing operational attention marker: {$priorities[$index]}");
        }

        $this->assertSame($positions, $sorted = $positions);
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Operational attention blocks must render in the approved priority order.');

        $response
            ->assertSee('Central de atenção')
            ->assertSee('Em execução')
            ->assertSee('Ações vencidas')
            ->assertSee('Sem avaliação')
            ->assertSee('Avaliações em elaboração');
    }

    public function test_executive_dashboard_marks_executive_navigation_as_current(): void
    {
        [$organization, $user] = $this->institutionalUser([
            AccessAbility::SCENARIOS_VIEW,
            AccessAbility::REPORTS_VIEW,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard.executive'))
            ->assertOk();

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('dashboard.executive'), '/').'"[^>]*aria-current="page"/',
            $response->getContent(),
        );
    }

    public function test_executive_navigation_remains_hidden_without_reports_ability(): void
    {
        [$organization, $user] = $this->institutionalUser([
            AccessAbility::SCENARIOS_VIEW,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('dashboard'))
            ->assertOk();

        $response->assertDontSee('href="'.route('dashboard.executive').'"', false);
    }

    private function institutionalUser(array $abilities): array
    {
        $organization = Organization::create([
            'name' => 'Comando M7',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'm7_dashboard',
            'abilities' => $abilities,
            'granted_at' => now(),
        ]);

        return [$organization, $user];
    }
}
