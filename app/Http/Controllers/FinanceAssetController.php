<?php

namespace App\Http\Controllers;

use App\Models\FinanceAsset;
use App\Models\FinanceLiability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceAssetController extends Controller
{
    /**
     * Display a listing of assets and liabilities (split layout).
     */
    public function index(): Response
    {
        $assets = FinanceAsset::orderBy('purchase_date', 'desc')->get();
        $liabilities = FinanceLiability::orderBy('due_date')->get();

        $totalAssets = $assets->sum('current_value');
        $totalLiabilities = $liabilities->sum('remaining_amount');

        return Inertia::render('Finance/AssetsLiabilities', [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
        ]);
    }

    /**
     * Store a newly created asset.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:property,vehicle,electronics,other'],
            'purchase_value' => ['required', 'numeric', 'min:0.01'],
            'current_value' => ['required', 'numeric', 'min:0.01'],
            'purchase_date' => ['required', 'date'],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['depreciation_rate'] = $validated['depreciation_rate'] ?? 0.00;

        FinanceAsset::create($validated);

        return redirect()->back()->with('success', 'Aset berhasil dicatat.');
    }

    /**
     * Update the specified asset.
     */
    public function update(Request $request, FinanceAsset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:property,vehicle,electronics,other'],
            'purchase_value' => ['required', 'numeric', 'min:0.01'],
            'current_value' => ['required', 'numeric', 'min:0.01'],
            'purchase_date' => ['required', 'date'],
            'depreciation_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $asset->update($validated);

        return redirect()->back()->with('success', 'Aset berhasil diubah.');
    }

    /**
     * Remove the specified asset.
     */
    public function destroy(FinanceAsset $asset): RedirectResponse
    {
        $asset->delete();

        return redirect()->back()->with('success', 'Aset berhasil dihapus.');
    }
}
