<?php

namespace App\Knowledge;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

final class KnowledgeRepository
{
    public function all(): Collection
    {
        return collect(config('knowledge.articles', []))
            ->map(fn (array $definition): KnowledgeArticle => $this->hydrate($definition))
            ->values();
    }

    public function find(string $slug): ?KnowledgeArticle
    {
        $definition = collect(config('knowledge.articles', []))
            ->first(fn (array $article): bool => ($article['slug'] ?? null) === $slug);

        if (! is_array($definition)) {
            return null;
        }

        return $this->hydrate($definition);
    }

    private function hydrate(array $definition): KnowledgeArticle
    {
        $path = $this->resolveSourcePath((string) ($definition['file'] ?? ''));
        $markdown = file_get_contents($path);

        if (! is_string($markdown)) {
            throw $this->sourceUnavailable();
        }

        $safeHtml = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $safeHtml = $this->withoutLeadingMarkdownTitle($safeHtml);
        [$html, $toc] = $this->decorateHeadings($safeHtml);

        return new KnowledgeArticle(
            slug: (string) ($definition['slug'] ?? ''),
            file: (string) ($definition['file'] ?? ''),
            title: (string) ($definition['title'] ?? ''),
            summary: (string) ($definition['summary'] ?? ''),
            category: (string) ($definition['category'] ?? ''),
            audience: array_values((array) ($definition['audience'] ?? [])),
            tags: array_values((array) ($definition['tags'] ?? [])),
            order: (int) ($definition['order'] ?? 0),
            reviewedOn: (string) ($definition['reviewed_on'] ?? ''),
            related: array_values((array) ($definition['related'] ?? [])),
            contextualFor: array_values((array) ($definition['contextual_for'] ?? [])),
            markdown: $markdown,
            html: $html,
            searchText: Str::squish(strip_tags($markdown)),
            toc: $toc,
        );
    }

    private function withoutLeadingMarkdownTitle(string $html): string
    {
        $withoutTitle = preg_replace('/^\s*<h1>.*?<\/h1>\s*/si', '', $html, 1);

        return is_string($withoutTitle) ? $withoutTitle : $html;
    }

    private function decorateHeadings(string $html): array
    {
        $headings = [];
        $occurrences = [];

        $decorated = preg_replace_callback(
            '/<(h[23])>(.*?)<\/\1>/si',
            function (array $matches) use (&$headings, &$occurrences): string {
                $label = trim(html_entity_decode(strip_tags($matches[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $base = Str::slug(Str::ascii($label));

                if ($base === '') {
                    $base = 'secao';
                }

                $occurrences[$base] = ($occurrences[$base] ?? 0) + 1;
                $id = $occurrences[$base] === 1 ? $base : $base.'-'.$occurrences[$base];

                $headings[] = [
                    'level' => (int) substr($matches[1], 1),
                    'label' => $label,
                    'id' => $id,
                ];

                return sprintf('<%1$s id="%2$s">%3$s</%1$s>', $matches[1], e($id), $matches[2]);
            },
            $html,
        );

        if (! is_string($decorated)) {
            $decorated = $html;
        }

        return [$decorated, count($headings) >= 2 ? $headings : []];
    }

    private function resolveSourcePath(string $file): string
    {
        if ($this->isUnsafeRelativePath($file)) {
            throw $this->sourceUnavailable();
        }

        $basePath = realpath(resource_path('knowledge/articles'));

        if ($basePath === false) {
            throw $this->sourceUnavailable();
        }

        $candidate = $basePath.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
        $resolved = realpath($candidate);

        if (
            $resolved === false
            || ! is_file($resolved)
            || ! str_starts_with($resolved, $basePath.DIRECTORY_SEPARATOR)
        ) {
            throw $this->sourceUnavailable();
        }

        return $resolved;
    }

    private function isUnsafeRelativePath(string $file): bool
    {
        if ($file === '' || str_contains($file, "\0")) {
            return true;
        }

        if (preg_match('/^(?:[\\\\\/]|[A-Za-z]:[\\\\\/])/', $file) === 1) {
            return true;
        }

        if (preg_match('/^[A-Za-z][A-Za-z0-9+.-]*:\/\//', $file) === 1) {
            return true;
        }

        return preg_match('/(?:^|[\\\\\/])\.\.(?:[\\\\\/]|$)/', $file) === 1;
    }

    private function sourceUnavailable(): RuntimeException
    {
        return new RuntimeException('Knowledge article source is unavailable.');
    }
}
