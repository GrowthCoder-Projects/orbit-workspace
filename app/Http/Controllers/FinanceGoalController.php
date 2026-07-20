<?php

namespace App\Http\Controllers;

use App\Models\FinanceGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinanceGoalController extends Controller
{
    /**
     * Store a newly created financial goal.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'current_amount' => ['nullable', 'numeric'],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['current_amount'] = $validated['current_amount'] ?? 0.00;

        FinanceGoal::create($validated);

        return redirect()->back()->with('success', 'Target keuangan berhasil dibuat.');
    }

    /**
     * Update the specified financial goal.
     */
    public function update(Request $request, FinanceGoal $goal): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'current_amount' => ['required', 'numeric'],
            'deadline' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $goal->update($validated);

        return redirect()->back()->with('success', 'Target keuangan berhasil diubah.');
    }

    /**
     * Remove the specified financial goal.
     */
    public function destroy(FinanceGoal $goal): RedirectResponse
    {
        $goal->delete();

        return redirect()->back()->with('success', 'Target keuangan berhasil dihapus.');
    }
}
