<?php

namespace Tests\Feature;

use App\Knowledge\KnowledgeRepository;
use DateTimeImmutable;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KnowledgeContentIntegrityTest extends TestCase
{
    public function test_catalog_metadata_relationships_and_context_routes_are_integral(): void
    {
        $articles = collect(config('knowledge.articles', []));
        $categories = array_keys((array) config('knowledge.categories', []));
        $audiences = (array) config('knowledge.audiences', []);
        $slugs = $articles->pluck('slug')->all();
        $files = $articles->pluck('file')->all();
        $contextRoutes = [];

        $this->assertNotEmpty($articles);
        $this->assertCount(count(array_unique($slugs)), $slugs, 'Knowledge slugs must be unique.');
        $this->assertCount(count(array_unique($files)), $files, 'Knowledge source files must be assigned once.');

        foreach ($articles as $definition) {
            foreach (['slug', 'file', 'title', 'summary', 'category', 'audience', 'tags', 'order', 'reviewed_on', 'related', 'contextual_for'] as $field) {
                $this->assertArrayHasKey($field, $definition, "Missing knowledge metadata field {$field}.");
            }

            $slug = $definition['slug'];
            $this->assertIsString($slug);
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
            $this->assertIsString($definition['file']);
            $this->assertNotSame('', trim($definition['file']));
            $this->assertIsString($definition['title']);
            $this->assertNotSame('', trim($definition['title']));
            $this->assertIsString($definition['summary']);
            $this->assertNotSame('', trim($definition['summary']));
            $this->assertContains($definition['category'], $categories);
            $this->assertIsArray($definition['audience']);
            $this->assertNotEmpty($definition['audience']);
            $this->assertEmpty(array_diff($definition['audience'], $audiences));
            $this->assertIsArray($definition['tags']);
            $this->assertNotEmpty($definition['tags']);
            $this->assertIsInt($definition['order']);
            $this->assertIsArray($definition['related']);
            $this->assertIsArray($definition['contextual_for']);

            $reviewedOn = DateTimeImmutable::createFromFormat('!Y-m-d', $definition['reviewed_on']);
            $this->assertNotFalse($reviewedOn, "Invalid review date for {$slug}.");
            $this->assertSame($definition['reviewed_on'], $reviewedOn->format('Y-m-d'));

            foreach ($definition['related'] as $relatedSlug) {
                $this->assertContains($relatedSlug, $slugs, "Unknown related knowledge slug {$relatedSlug}.");
            }

            foreach ($definition['contextual_for'] as $routeName) {
                $this->assertTrue(Route::has($routeName), "Unknown contextual route {$routeName}.");
                $contextRoutes[] = $routeName;
            }
        }

        $this->assertCount(
            count(array_unique($contextRoutes)),
            $contextRoutes,
            'A product route must not map to multiple contextual knowledge articles.',
        );
    }

    public function test_every_markdown_source_is_catalogued_and_internal_knowledge_links_resolve(): void
    {
        $articles = collect(config('knowledge.articles', []));
        $catalogFiles = $articles->pluck('file')->sort()->values()->all();
        $catalogSlugs = $articles->pluck('slug')->all();
        $directory = resource_path('knowledge/articles');
        $sourceFiles = collect(glob($directory.'/*.md') ?: [])
            ->map(fn (string $path): string => basename($path))
            ->sort()
            ->values()
            ->all();

        $this->assertSame($catalogFiles, $sourceFiles, 'Every shipped Markdown source must be catalogued exactly once.');

        foreach ($articles as $definition) {
            $markdown = file_get_contents($directory.'/'.$definition['file']);
            $this->assertIsString($markdown);
            $this->assertStringNotContainsString('<?php', $markdown);
            $this->assertStringNotContainsString('<script', mb_strtolower($markdown));

            preg_match_all('#/knowledge/([a-z0-9]+(?:-[a-z0-9]+)*)#', $markdown, $matches);
            foreach ($matches[1] ?? [] as $linkedSlug) {
                $this->assertContains($linkedSlug, $catalogSlugs, "Broken internal knowledge link {$linkedSlug}.");
            }
        }
    }

    public function test_contextual_help_is_resolved_from_catalog_metadata_not_a_second_hardcoded_map(): void
    {
        $repository = app(KnowledgeRepository::class);

        $this->assertSame('getting-started', $repository->findByContext('dashboard')?->slug);
        $this->assertSame('execution-cockpit', $repository->findByContext('executions.show')?->slug);
        $this->assertNull($repository->findByContext('health'));

        $layout = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
        $this->assertIsString($layout);
        $this->assertStringContainsString('findByContext', $layout);
        $this->assertStringNotContainsString("'scenarios.index'", $layout);
        $this->assertStringNotContainsString("'executions.show'", $layout);
    }
}
