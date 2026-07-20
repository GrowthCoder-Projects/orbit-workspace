<?php

namespace App\Http\Controllers;

use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class KbArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): InertiaResponse
    {
        $categories = KbCategory::orderBy('name')->get();
        $articles = KbArticle::with('categories')->orderBy('title')->get();

        return Inertia::render('KnowledgeBase/Index', [
            'categories' => $categories,
            'articles' => $articles,
            'selectedArticleId' => $request->query('id') ? (int) $request->query('id') : null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'parent_article_id' => ['nullable', 'exists:kb_articles,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:kb_categories,id'],
        ]);

        $article = KbArticle::create([
            'title' => $validated['title'],
            'parent_article_id' => $validated['parent_article_id'] ?? null,
            'content' => '',
        ]);

        // Sync categories only if it is a root article
        if (empty($validated['parent_article_id']) && ! empty($validated['category_ids'])) {
            $article->categories()->sync($validated['category_ids']);
        }

        return redirect()->route('kb.index', ['id' => $article->id])
            ->with('success', 'Article created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KbArticle $article): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'parent_article_id' => ['nullable', 'exists:kb_articles,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:kb_categories,id'],
        ]);

        // Prevent circular reference: parent_article_id cannot be self
        if (isset($validated['parent_article_id']) && $validated['parent_article_id'] == $article->id) {
            $validated['parent_article_id'] = null;
        }

        $article->update([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? '',
            'parent_article_id' => $validated['parent_article_id'] ?? null,
        ]);

        // Sync categories only if it is a root article.
        // If it was changed to a child article, we should detach categories.
        if (empty($validated['parent_article_id'])) {
            $article->categories()->sync($validated['category_ids'] ?? []);
        } else {
            $article->categories()->detach();
        }

        return redirect()->route('kb.index', ['id' => $article->id])
            ->with('success', 'Article updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KbArticle $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('kb.index')
            ->with('success', 'Article deleted successfully.');
    }
}
