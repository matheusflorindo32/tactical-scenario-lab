<?php

namespace Tests\Feature;

use App\Knowledge\KnowledgeRepository;
use RuntimeException;
use Tests\TestCase;

class KnowledgeRepositorySecurityTest extends TestCase
{
    private string $articleDirectory;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->articleDirectory = resource_path('knowledge/articles');
        $this->fixturePath = $this->articleDirectory.'/_m8-security-fixture.md';

        if (! is_dir($this->articleDirectory)) {
            mkdir($this->articleDirectory, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->fixturePath)) {
            unlink($this->fixturePath);
        }

        parent::tearDown();
    }

    public function test_unknown_slug_is_not_interpreted_as_a_file_path(): void
    {
        config()->set('knowledge.articles', []);

        $repository = app(KnowledgeRepository::class);

        $this->assertNull($repository->find('../../.env'));
        $this->assertNull($repository->find('file:///etc/passwd'));
        $this->assertNull($repository->find('resources/views/welcome.blade.php'));
    }

    public function test_allowlisted_article_source_must_remain_inside_knowledge_directory(): void
    {
        config()->set('knowledge.articles', [
            $this->articleDefinition('escape', '../views/welcome.blade.php'),
        ]);

        $repository = app(KnowledgeRepository::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Knowledge article source is unavailable.');

        $repository->find('escape');
    }

    public function test_absolute_and_url_wrapper_sources_fail_closed(): void
    {
        foreach (['/etc/passwd', 'file:///etc/passwd', 'php://filter/resource=/etc/passwd'] as $source) {
            config()->set('knowledge.articles', [
                $this->articleDefinition('unsafe-source', $source),
            ]);

            try {
                app(KnowledgeRepository::class)->find('unsafe-source');
                $this->fail("Unsafe source {$source} was accepted.");
            } catch (RuntimeException $exception) {
                $this->assertSame('Knowledge article source is unavailable.', $exception->getMessage());
                $this->assertStringNotContainsString('/etc/passwd', $exception->getMessage());
                $this->assertStringNotContainsString(resource_path(), $exception->getMessage());
            }
        }
    }

    public function test_allowlisted_markdown_is_rendered_without_executable_html_or_unsafe_links(): void
    {
        file_put_contents($this->fixturePath, <<<'MARKDOWN'
# Guia seguro

<script>alert('x')</script>
<img src=x onerror="alert('x')">

[Link inseguro](javascript:alert('x'))

{{ config('app.key') }}

<?php echo 'executed'; ?>

Texto permitido.
MARKDOWN);

        config()->set('knowledge.articles', [
            $this->articleDefinition('safe-guide', '_m8-security-fixture.md'),
        ]);

        $article = app(KnowledgeRepository::class)->find('safe-guide');

        $this->assertNotNull($article);
        $this->assertStringContainsString('Texto permitido.', $article->html);
        $this->assertStringNotContainsString('<script', $article->html);
        $this->assertStringNotContainsString('<img', $article->html);
        $this->assertStringNotContainsString('onerror=', $article->html);
        $this->assertStringNotContainsString('javascript:', $article->html);
        $this->assertStringContainsString("{{ config('app.key') }}", $article->html);
        $this->assertStringNotContainsString('executed', $article->html);
    }

    public function test_missing_allowlisted_source_fails_closed_without_path_leakage(): void
    {
        config()->set('knowledge.articles', [
            $this->articleDefinition('missing', 'missing-file.md'),
        ]);

        try {
            app(KnowledgeRepository::class)->find('missing');
            $this->fail('Missing knowledge source was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Knowledge article source is unavailable.', $exception->getMessage());
            $this->assertStringNotContainsString('missing-file.md', $exception->getMessage());
            $this->assertStringNotContainsString(resource_path(), $exception->getMessage());
        }
    }

    private function articleDefinition(string $slug, string $file): array
    {
        return [
            'slug' => $slug,
            'file' => $file,
            'title' => 'Guia de teste',
            'summary' => 'Contrato de segurança do Knowledge Center.',
            'category' => 'getting-started',
            'audience' => ['instructor'],
            'tags' => ['teste'],
            'order' => 10,
            'reviewed_on' => '2026-08-09',
            'related' => [],
            'contextual_for' => [],
        ];
    }
}
