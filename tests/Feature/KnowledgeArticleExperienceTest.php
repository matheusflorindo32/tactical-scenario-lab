<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeArticleExperienceTest extends TestCase
{
    use RefreshDatabase;

    private array $fixturePaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $directory = resource_path('knowledge/articles');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $main = $directory.'/_m8-article-main.md';
        $related = $directory.'/_m8-article-related.md';
        $this->fixturePaths = [$main, $related];

        file_put_contents($main, <<<'MARKDOWN'
# Guia principal

Introdução do artigo.

## Execução

Conteúdo da primeira seção.

### Preparação

Conteúdo da subseção.

## Execução

Conteúdo da seção duplicada.
MARKDOWN);
        file_put_contents($related, "# Guia relacionado\n\nConteúdo relacionado.\n");

        config()->set('knowledge.articles', [
            $this->articleDefinition(
                slug: 'execution-cockpit',
                file: '_m8-article-main.md',
                title: 'Cockpit de execução',
                summary: 'Entenda a condução da execução dentro do produto.',
                related: ['getting-started'],
            ),
            $this->articleDefinition(
                slug: 'getting-started',
                file: '_m8-article-related.md',
                title: 'Primeiros passos',
                summary: 'Conheça o fluxo institucional do produto.',
            ),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->fixturePaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_unknown_knowledge_slug_returns_404_inside_authenticated_boundary(): void
    {
        [$organization, $user] = $this->institutionalUser();

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/knowledge/not-in-catalog')
            ->assertNotFound();
    }

    public function test_article_uses_institutional_reading_surface_with_metadata_and_related_guides(): void
    {
        [$organization, $user] = $this->institutionalUser();

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/knowledge/execution-cockpit')
            ->assertOk();

        $response
            ->assertSee('Base de Conhecimento')
            ->assertSee('Cockpit de execução')
            ->assertSee('Entenda a condução da execução dentro do produto.')
            ->assertSee('Primeiros passos')
            ->assertSee('09/08/2026')
            ->assertSee('Conteúdo da primeira seção.')
            ->assertSee('href="'.route('knowledge.index').'"', false)
            ->assertSee('href="'.route('knowledge.show', 'getting-started').'"', false)
            ->assertDontSee('_m8-article-main.md')
            ->assertDontSee(resource_path());
    }

    public function test_article_generates_deterministic_h2_h3_table_of_contents_and_heading_ids(): void
    {
        [$organization, $user] = $this->institutionalUser();

        $response = $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/knowledge/execution-cockpit')
            ->assertOk();

        $html = $response->getContent();

        $response
            ->assertSee('Neste guia')
            ->assertSee('href="#execucao"', false)
            ->assertSee('href="#preparacao"', false)
            ->assertSee('href="#execucao-2"', false)
            ->assertSee('id="execucao"', false)
            ->assertSee('id="preparacao"', false)
            ->assertSee('id="execucao-2"', false);

        $this->assertLessThan(strpos($html, 'href="#preparacao"'), strpos($html, 'href="#execucao"'));
        $this->assertLessThan(strpos($html, 'href="#execucao-2"'), strpos($html, 'href="#preparacao"'));
    }

    public function test_single_eligible_heading_does_not_render_table_of_contents(): void
    {
        file_put_contents($this->fixturePaths[0], "# Guia principal\n\n## Única seção\n\nConteúdo.\n");
        [$organization, $user] = $this->institutionalUser();

        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get('/knowledge/execution-cockpit')
            ->assertOk()
            ->assertDontSee('Neste guia');
    }

    private function articleDefinition(
        string $slug,
        string $file,
        string $title,
        string $summary,
        array $related = [],
    ): array {
        return [
            'slug' => $slug,
            'file' => $file,
            'title' => $title,
            'summary' => $summary,
            'category' => 'operation',
            'audience' => ['instructor', 'evaluator'],
            'tags' => ['produto'],
            'order' => 10,
            'reviewed_on' => '2026-08-09',
            'related' => $related,
            'contextual_for' => [],
        ];
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
