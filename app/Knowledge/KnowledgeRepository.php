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

    public function findByContext(string $routeName): ?KnowledgeArticle
    {
        $definition = collect(config('knowledge.articles', []))
            ->first(function (array $article) use ($routeName): bool {
                return in_array($routeName, (array) ($article['contextual_for'] ?? []), true);
            });

        if (! is_array($definition)) {
            return null;
        }

        return $this->hydrate($definition);
    }

    public function search(string $query, ?string $category = null): Collection
    {
        $articles = $this->all();
        $categories = (array) config('knowledge.categories', []);

        if ($category !== null && $category !== '' && array_key_exists($category, $categories)) {
            $articles = $articles
                ->filter(fn (KnowledgeArticle $article): bool => $article->category === $category)
                ->values();
        }

        $normalizedQuery = $this->normalizeSearchValue($query);

        if ($normalizedQuery === '') {
            return $articles
                ->values()
                ->map(fn (KnowledgeArticle $article, int $index): array => [
                    'article' => $article,
                    'index' => $index,
                ])
                ->sort(function (array $left, array $right): int {
                    $order = $left['article']->order <=> $right['article']->order;

                    return $order !== 0 ? $order : $left['index'] <=> $right['index'];
                })
                ->pluck('article')
                ->values();
        }

        return $articles
            ->map(function (KnowledgeArticle $article) use ($normalizedQuery, $categories): array {
                return [
                    'article' => $article,
                    'score' => $this->searchScore($article, $normalizedQuery, $categories),
                    'normalized_title' => $this->normalizeSearchValue($article->title),
                ];
            })
            ->filter(fn (array $result): bool => $result['score'] > 0)
            ->sort(function (array $left, array $right): int {
                $score = $right['score'] <=> $left['score'];
                if ($score !== 0) {
                    return $score;
                }

                $order = $left['article']->order <=> $right['article']->order;
                if ($order !== 0) {
                    return $order;
                }

                $title = $left['normalized_title'] <=> $right['normalized_title'];
                if ($title !== 0) {
                    return $title;
                }

                return $left['article']->slug <=> $right['article']->slug;
            })
            ->pluck('article')
            ->values();
    }

    private function searchScore(KnowledgeArticle $article, string $query, array $categories): int
    {
        $title = $this->normalizeSearchValue($article->title);

        if ($title === $query) {
            return 100;
        }

        $titleTokens = preg_split('/\s+/', $title) ?: [];
        if (
            str_starts_with($title, $query)
            || collect($titleTokens)->contains(fn (string $token): bool => str_starts_with($token, $query))
        ) {
            return 60;
        }

        foreach ($article->tags as $tag) {
            if (str_contains($this->normalizeSearchValue((string) $tag), $query)) {
                return 40;
            }
        }

        $summary = $this->normalizeSearchValue($article->summary);
        $categoryLabel = $this->normalizeSearchValue((string) ($categories[$article->category] ?? $article->category));
        if (str_contains($summary, $query) || str_contains($categoryLabel, $query)) {
            return 20;
        }

        if (str_contains($this->normalizeSearchValue($article->searchText), $query)) {
            return 10;
        }

        return 0;
    }

    private function normalizeSearchValue(string $value): string
    {
        return Str::lower(Str::ascii(Str::squish($value)));
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
