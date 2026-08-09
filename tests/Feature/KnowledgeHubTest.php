<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KnowledgeHubTest extends TestCase
{
    use RefreshDatabase;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $directory = resource_path('knowledge/articles');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $this->fixturePath = $directory.'/_m8-hub-fixture.md';
        file_put_contents($this->fixturePath, "# Primeiros passos\n\nConteúdo de descoberta do produto.\n");

        config()->set('knowledge.articles', [
            [
                'slug' => 'getting-started',
                'file' => '_m8-hub-fixture.md',
                'title' => 'Primeiros passos',
                'summary' => 'Entenda o fluxo institucional do Tactical Scenario Lab.',
                'category' => 'getting-started',
                'audience' => ['instructor', 'evaluator'],
                'tags' => ['início', 'navegação'],
                'order' => 10,
                'reviewed_on' => '2026-08-09',
                'related' => [],
                'contextual_for' => [],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->fixturePath)) {
            unlink($this->fixturePath);
        }

        parent::tearDown();
    }

    public function test_knowledge_routes_are_named_and_guest_is_redirected_to_login(): void
    {
        $this->assertTrue(Route::has('knowledge.index'));
        $this->assertTrue(Route::has('knowledge.show'));

        $this->get('/knowledge')
            ->assertRedirect(route('login'));
    }

    public function test_inactive_account_is_blocked_by_existing_account_boundary(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        $this->actingAs($user)
            ->get('/knowledge')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_active_authenticated_account_can_open_accessible_knowledge_hub(): void
    {
        [$organization, $user] = $this->institutionalUser();

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/knowledge')
            ->assertOk();

        $html = $response->getContent();

        $response
            ->assertSee('Base de Conhecimento')
            ->assertSee('method="GET"', false)
            ->assertSee('name="q"', false)
            ->assertSee('name="category"', false)
            ->assertSee('Primeiros passos')
            ->assertSee('Instrutor')
            ->assertSee('Avaliador')
            ->assertSee('09/08/2026')
            ->assertSee('1 guia encontrado')
            ->assertSee('href="'.url('/knowledge/getting-started').'"', false)
            ->assertSee('href="'.route('knowledge.index').'"', false);

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('knowledge.index'), '/').'"[^>]*aria-current="page"/',
            $html,
        );
    }

    public function test_knowledge_hub_has_accessible_empty_state(): void
    {
        [$organization, $user] = $this->institutionalUser();
        config()->set('knowledge.articles', []);

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/knowledge')
            ->assertOk()
            ->assertSee('Nenhum guia encontrado')
            ->assertSee('0 guias encontrados');
    }

    private function institutionalUser(): array
    {
        $organization = Organization::create([
            'name' => 'Centro Operacional M8',
            'kind' => 'company',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['status' => 'active']);

        UserOrganizationAccess::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'm8_reader',
            'abilities' => AccessAbility::all(),
            'granted_at' => now(),
        ]);

        return [$organization, $user];
    }
}
