<?php

namespace Tests\Feature;

use Tests\TestCase;

class KnowledgeInitialContentTest extends TestCase
{
    private const REQUIRED_SLUGS = [
        'getting-started',
        'scenarios-and-versioning',
        'execution-cockpit',
        'assessment-and-debrief',
        'history-and-reports',
        'people-organizations-access',
    ];

    public function test_initial_catalog_ships_the_six_required_product_guides(): void
    {
        $slugs = collect(config('knowledge.articles', []))
            ->pluck('slug')
            ->values()
            ->all();

        $this->assertSame(self::REQUIRED_SLUGS, $slugs);
    }

    public function test_initial_guides_have_reviewable_metadata_and_meaningful_section_depth(): void
    {
        $articles = collect(config('knowledge.articles', []))->keyBy('slug');
        $categories = array_keys((array) config('knowledge.categories', []));
        $audiences = (array) config('knowledge.audiences', []);

        foreach (self::REQUIRED_SLUGS as $slug) {
            $definition = $articles->get($slug);
            $this->assertIsArray($definition, "Missing initial guide {$slug}.");
            $this->assertNotSame('', trim((string) ($definition['title'] ?? '')));
            $this->assertNotSame('', trim((string) ($definition['summary'] ?? '')));
            $this->assertContains($definition['category'] ?? null, $categories);
            $this->assertNotEmpty($definition['audience'] ?? []);
            $this->assertEmpty(array_diff((array) ($definition['audience'] ?? []), $audiences));
            $this->assertNotEmpty($definition['tags'] ?? []);
            $this->assertIsInt($definition['order'] ?? null);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) ($definition['reviewed_on'] ?? ''));

            $source = resource_path('knowledge/articles/'.($definition['file'] ?? ''));
            $this->assertFileExists($source);
            $markdown = file_get_contents($source);
            $this->assertIsString($markdown);
            $this->assertGreaterThanOrEqual(2, preg_match_all('/^##\s+/m', $markdown));
            $this->assertGreaterThanOrEqual(80, str_word_count(strip_tags($markdown)));
        }
    }

    public function test_execution_guide_explicitly_stays_product_descriptive_not_autonomous_guidance(): void
    {
        $definition = collect(config('knowledge.articles', []))
            ->firstWhere('slug', 'execution-cockpit');

        $this->assertIsArray($definition);

        $markdown = file_get_contents(resource_path('knowledge/articles/'.$definition['file']));
        $this->assertIsString($markdown);
        $this->assertStringContainsString('não prescreve conduta clínica ou tática', mb_strtolower($markdown));
        $this->assertStringContainsString('timeline', mb_strtolower($markdown));
        $this->assertStringContainsString('append-only', mb_strtolower($markdown));
    }
}
