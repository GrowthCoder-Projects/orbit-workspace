<?php

namespace App\Http\Controllers;

use App\Jobs\FetchBookmarkMetadataJob;
use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BookmarkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): InertiaResponse
    {
        $categories = BookmarkCategory::withCount('bookmarks')->orderBy('name')->get();
        $bookmarks = Bookmark::with(['category', 'tags'])->latest()->get();
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Bookmarks/Index', [
            'categories' => $categories,
            'bookmarks' => $bookmarks,
            'tags' => $tags,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'category_id' => ['nullable', 'exists:bookmark_categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $host = parse_url($validated['url'], PHP_URL_HOST) ?? $validated['url'];

        $bookmark = Bookmark::create([
            'url' => $validated['url'],
            'category_id' => $validated['category_id'] ?? null,
            'status' => 'pending',
            'title' => $host,
        ]);

        if (! empty($validated['tags'])) {
            $tagIds = [];
            foreach ($validated['tags'] as $tagName) {
                if (trim($tagName) === '') {
                    continue;
                }
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($tagName)],
                    ['name' => trim($tagName)]
                );
                $tagIds[] = $tag->id;
            }
            $bookmark->tags()->sync($tagIds);
        }

        // Dispatch background metadata scraper
        FetchBookmarkMetadataJob::dispatch($bookmark);

        return back()->with('success', 'Bookmark added successfully. Fetching details in the background.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bookmark $bookmark): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'category_id' => ['nullable', 'exists:bookmark_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $urlChanged = $bookmark->url !== $validated['url'];

        $bookmark->update([
            'url' => $validated['url'],
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        $tagIds = [];
        if (isset($validated['tags'])) {
            foreach ($validated['tags'] as $tagName) {
                if (trim($tagName) === '') {
                    continue;
                }
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($tagName)],
                    ['name' => trim($tagName)]
                );
                $tagIds[] = $tag->id;
            }
        }
        $bookmark->tags()->sync($tagIds);

        // If the URL was modified, re-trigger metadata fetch
        if ($urlChanged) {
            $bookmark->update(['status' => 'pending']);
            FetchBookmarkMetadataJob::dispatch($bookmark);
        }

        return back()->with('success', 'Bookmark updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bookmark $bookmark): RedirectResponse
    {
        $bookmark->delete();

        return back()->with('success', 'Bookmark deleted successfully.');
    }

    /**
     * Toggle the favorite status of a bookmark.
     */
    public function toggleFavorite(Bookmark $bookmark): RedirectResponse
    {
        $bookmark->update([
            'is_favorite' => ! $bookmark->is_favorite,
        ]);

        return back()->with('success', $bookmark->is_favorite ? 'Bookmark added to favorites.' : 'Bookmark removed from favorites.');
    }
}
