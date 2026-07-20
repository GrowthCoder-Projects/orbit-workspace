<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FinanceTransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): Response
    {
        $query = FinanceTransaction::with(['account', 'destinationAccount', 'category', 'client'])
            ->latest('transaction_date')
            ->latest('id');

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('account', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Type filter
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Account filter
        if ($accountId = $request->input('account_id')) {
            $query->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                  ->orWhere('destination_account_id', $accountId);
            });
        }

        // Category filter
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Client filter
        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Date range filters
        if ($startDate = $request->input('start_date')) {
            $query->where('transaction_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $transactions = $query->paginate(20)->withQueryString();

        $accounts = FinanceAccount::orderBy('name')->get();
        $categories = FinanceCategory::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        return Inertia::render('Finance/Transactions', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'categories' => $categories,
            'clients' => $clients,
            'filters' => $request->only(['search', 'type', 'account_id', 'category_id', 'client_id', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'exists:finance_accounts,id'],
            'destination_account_id' => ['nullable', 'required_if:type,transfer', 'exists:finance_accounts,id', 'different:account_id'],
            'category_id' => ['nullable', 'required_if:type,income,type,expense', 'exists:finance_categories,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'converted_amount' => ['nullable', 'numeric', 'min:0.01'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'attachment' => ['nullable', 'file', 'max:10240'], // Max 10MB
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('finance_attachments', 'public');
            $validated['attachment_path'] = $path;
        }

        // Process multi-currency transfer variables
        if ($validated['type'] === 'transfer') {
            $sourceAccount = FinanceAccount::findOrFail($validated['account_id']);
            $destAccount = FinanceAccount::findOrFail($validated['destination_account_id']);

            if ($sourceAccount->currency === $destAccount->currency) {
                $validated['converted_amount'] = $validated['amount'];
                $validated['exchange_rate'] = 1.000000;
            } else {
                // If exchange rate is not provided, calculate it or default to 1
                $rate = $validated['exchange_rate'] ?? 1.000000;
                $validated['exchange_rate'] = $rate;
                $validated['converted_amount'] = $validated['converted_amount'] ?? ($validated['amount'] * $rate);
            }
        } else {
            $validated['destination_account_id'] = null;
            $validated['converted_amount'] = null;
            $validated['exchange_rate'] = 1.000000;
        }

        FinanceTransaction::create($validated);

        return redirect()->back()->with('success', 'Transaksi berhasil dicatat.');
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, FinanceTransaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'exists:finance_accounts,id'],
            'destination_account_id' => ['nullable', 'required_if:type,transfer', 'exists:finance_accounts,id', 'different:account_id'],
            'category_id' => ['nullable', 'required_if:type,income,type,expense', 'exists:finance_categories,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'converted_amount' => ['nullable', 'numeric', 'min:0.01'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($request->hasFile('attachment')) {
            // Delete old attachment if exists
            if ($transaction->attachment_path) {
                Storage::disk('public')->delete($transaction->attachment_path);
            }
            $path = $request->file('attachment')->store('finance_attachments', 'public');
            $validated['attachment_path'] = $path;
        }

        // Process multi-currency transfer variables
        if ($validated['type'] === 'transfer') {
            $sourceAccount = FinanceAccount::findOrFail($validated['account_id']);
            $destAccount = FinanceAccount::findOrFail($validated['destination_account_id']);

            if ($sourceAccount->currency === $destAccount->currency) {
                $validated['converted_amount'] = $validated['amount'];
                $validated['exchange_rate'] = 1.000000;
            } else {
                $rate = $validated['exchange_rate'] ?? 1.000000;
                $validated['exchange_rate'] = $rate;
                $validated['converted_amount'] = $validated['converted_amount'] ?? ($validated['amount'] * $rate);
            }
        } else {
            $validated['destination_account_id'] = null;
            $validated['converted_amount'] = null;
            $validated['exchange_rate'] = 1.000000;
        }

        $transaction->update($validated);

        return redirect()->back()->with('success', 'Transaksi berhasil diubah.');
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(FinanceTransaction $transaction): RedirectResponse
    {
        if ($transaction->attachment_path) {
            Storage::disk('public')->delete($transaction->attachment_path);
        }

        $transaction->delete();

        return redirect()->back()->with('success', 'Transaksi berhasil dihapus.');
    }
}
