<?php

namespace App\Http\Controllers;

use App\Models\FinanceAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceAccountController extends Controller
{
    /**
     * Display a listing of accounts.
     */
    public function index(): Response
    {
        $accounts = FinanceAccount::withCount(['transactions', 'destinationTransactions'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Finance/Accounts', [
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:cash,bank,e-wallet,credit_card,investment'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'balance' => ['nullable', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'color' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['balance'] = $validated['balance'] ?? 0.00;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos/accounts', 'public');
            $validated['logo_path'] = $logoPath;
        }

        FinanceAccount::create($validated);

        return redirect()->back()->with('success', 'Rekening/Akun berhasil dibuat.');
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, FinanceAccount $account): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:cash,bank,e-wallet,credit_card,investment'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'balance' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'color' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos/accounts', 'public');
            $validated['logo_path'] = $logoPath;
        }

        $account->update($validated);

        return redirect()->back()->with('success', 'Rekening/Akun berhasil diubah.');
    }

    /**
     * Remove the specified account.
     */
    public function destroy(FinanceAccount $account): RedirectResponse
    {
        $account->delete();

        return redirect()->back()->with('success', 'Rekening/Akun berhasil dihapus.');
    }
}
