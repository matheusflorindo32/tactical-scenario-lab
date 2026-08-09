<?php

namespace App\Http\Controllers;

use App\Knowledge\KnowledgeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class KnowledgeController extends Controller
{
    public function index(Request $request, KnowledgeRepository $repository): View
    {
        $categories = (array) config('knowledge.categories', []);
        $requestedCategory = trim((string) $request->query('category', ''));
        $selectedCategory = array_key_exists($requestedCategory, $categories) ? $requestedCategory : '';
        $query = Str::squish((string) $request->query('q', ''));
        $articles = $repository->search($query, $selectedCategory !== '' ? $selectedCategory : null);

        return view('knowledge.index', [
            'articles' => $articles,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
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
