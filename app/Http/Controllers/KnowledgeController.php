<?php

namespace App\Http\Controllers;

use App\Knowledge\KnowledgeRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class KnowledgeController extends Controller
{
    public function index(Request $request, KnowledgeRepository $repository): View
    {
        $categories = (array) config('knowledge.categories', []);
        $category = trim((string) $request->query('category', ''));
        $query = trim((string) $request->query('q', ''));
        $articles = $repository->all();

        if ($category !== '' && array_key_exists($category, $categories)) {
            $articles = $articles
                ->filter(fn ($article): bool => $article->category === $category)
                ->values();
        }

        return view('knowledge.index', [
            'articles' => $articles,
            'categories' => $categories,
            'selectedCategory' => $category,
            'query' => $query,
        ]);
    }

    public function show(string $slug, KnowledgeRepository $repository): View
    {
        $article = $repository->find($slug);

        abort_if($article === null, 404);

        $relatedArticles = collect($article->related)
            ->map(fn (string $relatedSlug) => $repository->find($relatedSlug))
            ->filter()
            ->values();

        return view('knowledge.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'categories' => (array) config('knowledge.categories', []),
        ]);
    }
}
