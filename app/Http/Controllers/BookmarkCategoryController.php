<?php

namespace App\Http\Controllers;

use App\Models\BookmarkCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookmarkCategoryController extends Controller
{
    /**
     * Store a newly created bookmark category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        BookmarkCategory::create($validated);

        return back()->with('success', 'Category created successfully.');
    }

    /**
     * Update the specified bookmark category in storage.
     */
    public function update(Request $request, BookmarkCategory $bookmarkCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $bookmarkCategory->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified bookmark category from storage.
     */
    public function destroy(BookmarkCategory $bookmarkCategory): RedirectResponse
    {
        $bookmarkCategory->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}
