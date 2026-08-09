<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M7ManagementExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_indexes_use_canonical_brand_navigation_and_table_primitives(): void
    {
        [$organization, $user] = $this->institutionalUser();
        $person = Person::create([
            'display_name' => 'Operador Gestão M7',
            'status' => 'active',
        ]);
        OrganizationMembership::create([
            'person_id' => $person->id,
            'organization_id' => $organization->id,
            'position' => 'Instrutor',
            'started_at' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id]);

        $people = $this->get(route('people.index'))
            ->assertOk()
            ->assertSee('aria-label="Pessoas da organização"', false)
            ->assertDontSee('Tactical Medicine Academy');
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('people.index'), '/').'"[^>]*aria-current="page"/',
            $people->getContent(),
        );

        $organizations = $this->get(route('organizations.index'))
            ->assertOk()
            ->assertSee('aria-label="Organizações acessíveis"', false)
            ->assertDontSee('Tactical Medicine Academy');
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('organizations.index'), '/').'"[^>]*aria-current="page"/',
            $organizations->getContent(),
        );

        $access = $this->get(route('access.index'))
            ->assertOk()
            ->assertSee('aria-label="Acessos institucionais"', false)
            ->assertDontSee('Tactical Medicine Academy');
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('access.index'), '/').'"[^>]*aria-current="page"/',
            $access->getContent(),
        );
    }

    public function test_management_index_views_do_not_use_undefined_legacy_color_tokens(): void
    {
        foreach ([
            resource_path('views/people/index.blade.php'),
            resource_path('views/organizations/index.blade.php'),
            resource_path('views/access/index.blade.php'),
        ] as $path) {
            $content = file_get_contents($path);

            $this->assertIsString($content);
            $this->assertDoesNotMatchRegularExpression(
                '/(?:ink-(?:200|600|800)|emergency-(?:200|800)|clinical-(?:200|800)|amber-\d+)/',
                $content,
                "Legacy/undefined design token remains in {$path}",
            );
        }
    }

    private function institutionalUser(): array
    {
        $organization = Organization::create([
            'name' => 'Centro Gestão M7',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'm7_admin',
            'abilities' => AccessAbility::all(),
            'granted_at' => now(),
        ]);

        return [$organization, $user];
    }
}
