<?php

namespace App\Http\Controllers;

use App\Models\FinanceLiability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinanceLiabilityController extends Controller
{
    /**
     * Store a newly created liability.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:loan,credit_card,paylater,installment'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'remaining_amount' => ['required', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['interest_rate'] = $validated['interest_rate'] ?? 0.00;

        FinanceLiability::create($validated);

        return redirect()->back()->with('success', 'Kewajiban/Utang berhasil dicatat.');
    }

    /**
     * Update the specified liability.
     */
    public function update(Request $request, FinanceLiability $liability): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:loan,credit_card,paylater,installment'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'remaining_amount' => ['required', 'numeric', 'min:0'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $liability->update($validated);

        return redirect()->back()->with('success', 'Kewajiban/Utang berhasil diubah.');
    }

    /**
     * Remove the specified liability.
     */
    public function destroy(FinanceLiability $liability): RedirectResponse
    {
        $liability->delete();

        return redirect()->back()->with('success', 'Kewajiban/Utang berhasil dihapus.');
    }
}
