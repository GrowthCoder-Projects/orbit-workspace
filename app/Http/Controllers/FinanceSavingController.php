<?php

namespace App\Http\Controllers;

use App\Models\FinanceSaving;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceSavingController extends Controller
{
    /**
     * Display a listing of savings.
     */
    public function index(): Response
    {
        $savings = FinanceSaving::orderBy('name')->get();

        return Inertia::render('Finance/Savings', [
            'savings' => $savings,
            'goals' => \App\Models\FinanceGoal::orderBy('deadline')->get(),
        ]);
    }

    /**
     * Store a newly created saving target.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'current_amount' => ['nullable', 'numeric'],
            'target_date' => ['nullable', 'date'],
            'currency' => ['required', 'string', 'size:3'],
            'color' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['current_amount'] = $validated['current_amount'] ?? 0.00;

        FinanceSaving::create($validated);

        return redirect()->back()->with('success', 'Target tabungan berhasil dibuat.');
    }

    /**
     * Update the specified saving target.
     */
    public function update(Request $request, FinanceSaving $saving): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'current_amount' => ['required', 'numeric'],
            'target_date' => ['nullable', 'date'],
            'currency' => ['required', 'string', 'size:3'],
            'color' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $saving->update($validated);

        return redirect()->back()->with('success', 'Target tabungan berhasil diubah.');
    }

    /**
     * Remove the specified saving target.
     */
    public function destroy(FinanceSaving $saving): RedirectResponse
    {
        $saving->delete();

        return redirect()->back()->with('success', 'Target tabungan berhasil dihapus.');
    }
}
