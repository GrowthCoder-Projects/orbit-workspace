<?php

namespace App\Http\Controllers;

use App\Models\FinanceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceCategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(): Response
    {
        $categories = FinanceCategory::withCount('transactions')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return Inertia::render('Finance/Categories', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
        ]);

        FinanceCategory::create($validated);

        return redirect()->back()->with('success', 'Kategori berhasil dibuat.');
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, FinanceCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', 'Kategori berhasil diubah.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(FinanceCategory $category): RedirectResponse
    {
        $category->delete();

        return redirect()->back()->with('success', 'Kategori berhasil dihapus.');
    }
}
