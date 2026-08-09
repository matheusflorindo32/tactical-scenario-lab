<?php

namespace Tests\Feature;

use App\Knowledge\KnowledgeRepository;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganizationAccess;
use App\Support\Auth\AccessAbility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeSearchTest extends TestCase
{
    use RefreshDatabase;

    private array $fixturePaths = [];

    protected function tearDown(): void
    {
        foreach ($this->fixturePaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_search_is_case_and_accent_insensitive_and_collapses_whitespace(): void
    {
        $this->configureArticles([
            $this->article('assessment', 'Avaliação e debrief', 'Fluxo do avaliador.', ['rubrica'], 10, 'assessment', 'Conteúdo neutro.'),
            $this->article('execution', 'Cockpit de execução', 'Fluxo operacional.', ['timeline'], 20, 'operation', 'Conteúdo neutro.'),
        ]);

        $slugs = app(KnowledgeRepository::class)
            ->search('   AVALIACAO    E   DEBRIEF   ')
            ->pluck('slug')
            ->all();

        $this->assertSame(['assessment'], $slugs);
    }

    public function test_search_uses_the_documented_weight_order(): void
    {
        $this->configureArticles([
            $this->article('body', 'Registro histórico', 'Consulta de conteúdo.', ['registro'], 50, 'governance', 'O termo alpha aparece somente no corpo.'),
            $this->article('summary', 'Relatórios', 'Alpha aparece no resumo.', ['relatorio'], 40, 'governance', 'Conteúdo neutro.'),
            $this->article('tag', 'Templates', 'Reuso institucional.', ['alpha'], 30, 'operation', 'Conteúdo neutro.'),
            $this->article('title-prefix', 'Alpha operacional', 'Guia de operação.', ['fluxo'], 20, 'operation', 'Conteúdo neutro.'),
            $this->article('title-exact', 'Alpha', 'Guia principal.', ['fluxo'], 10, 'getting-started', 'Conteúdo neutro.'),
        ]);

        $slugs = app(KnowledgeRepository::class)
            ->search('alpha')
            ->pluck('slug')
            ->all();

        $this->assertSame([
            'title-exact',
            'title-prefix',
            'tag',
            'summary',
            'body',
        ], $slugs);
    }

    public function test_search_ties_are_deterministic_and_empty_query_preserves_catalog_order(): void
    {
        $this->configureArticles([
            $this->article('zulu', 'Zulu', 'Termo beta.', ['x'], 30, 'operation', 'Conteúdo.'),
            $this->article('bravo', 'Bravo', 'Termo beta.', ['x'], 10, 'operation', 'Conteúdo.'),
            $this->article('alpha-b', 'Álpha', 'Termo beta.', ['x'], 20, 'operation', 'Conteúdo.'),
            $this->article('alpha-a', 'Alpha', 'Termo beta.', ['x'], 20, 'operation', 'Conteúdo.'),
        ]);

        $repository = app(KnowledgeRepository::class);

        $this->assertSame(
            ['bravo', 'alpha-a', 'alpha-b', 'zulu'],
            $repository->search('beta')->pluck('slug')->all(),
        );
        $this->assertSame(
            ['bravo', 'alpha-b', 'alpha-a', 'zulu'],
            $repository->search('')->pluck('slug')->all(),
        );
    }

    public function test_category_filter_is_controlled_and_hub_applies_query_server_side(): void
    {
        $this->configureArticles([
            $this->article('assessment', 'Avaliação e debrief', 'Fluxo do avaliador.', ['rubrica'], 10, 'assessment', 'Conteúdo.'),
            $this->article('execution', 'Cockpit de execução', 'Fluxo operacional.', ['timeline'], 20, 'operation', 'Conteúdo.'),
        ]);

        $repository = app(KnowledgeRepository::class);
        $this->assertSame(['execution'], $repository->search('', 'operation')->pluck('slug')->all());
        $this->assertSame(['assessment', 'execution'], $repository->search('', 'not-a-category')->pluck('slug')->all());

        [$organization, $user] = $this->institutionalUser();
        $this->actingAs($user)
            ->withSession(['active_organization_id' => $organization->id])
            ->get(route('knowledge.index', ['q' => 'avaliacao']))
            ->assertOk()
            ->assertSee('Avaliação e debrief')
            ->assertDontSee('Cockpit de execução')
            ->assertSee('1 guia encontrado');
    }

    private function configureArticles(array $articles): void
    {
        config()->set('knowledge.articles', $articles);
    }

    private function article(
        string $slug,
        string $title,
        string $summary,
        array $tags,
        int $order,
        string $category,
        string $body,
    ): array {
        $directory = resource_path('knowledge/articles');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $file = '_m8-search-'.$slug.'.md';
        $path = $directory.'/'.$file;
        file_put_contents($path, "# {$title}\n\n## Seção\n\n{$body}\n");
        $this->fixturePaths[] = $path;

        return [
            'slug' => $slug,
            'file' => $file,
            'title' => $title,
            'summary' => $summary,
            'category' => $category,
            'audience' => ['instructor'],
            'tags' => $tags,
            'order' => $order,
            'reviewed_on' => '2026-08-09',
            'related' => [],
            'contextual_for' => [],
        ];
    }

    private function institutionalUser(): array
    {
        $organization = Organization::create([
            'name' => 'Centro de Busca M8',
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
