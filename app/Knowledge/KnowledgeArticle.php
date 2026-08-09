<?php

namespace App\Knowledge;

final readonly class KnowledgeArticle
{
    public function __construct(
        public string $slug,
        public string $file,
        public string $title,
        public string $summary,
        public string $category,
        public array $audience,
        public array $tags,
        public int $order,
        public string $reviewedOn,
        public array $related,
        public array $contextualFor,
        public string $markdown,
        public string $html,
        public string $searchText,
        public array $toc,
    ) {}
}
