<?php

namespace App\Http\Controllers;

use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceBillController extends Controller
{
    /**
     * Display a listing of bills and subscriptions.
     */
    public function index(): Response
    {
        $bills = FinanceBill::with(['category', 'account'])->orderBy('due_day')->get();
        $accounts = FinanceAccount::orderBy('name')->get();
        $categories = FinanceCategory::where('type', 'expense')->orderBy('name')->get();

        $now = now();
        $totalMonthly = $bills->where('is_active', true)->where('recurrence_period', 'monthly')->sum('amount');
        $totalAnnual = $bills->where('is_active', true)->where('recurrence_period', 'annual')->sum('amount');
        $unpaidCount = $bills->where('is_active', true)->filter(function ($b) use ($now) {
            if (! $b->last_paid_at) {
                return true;
            }

            return ! ($b->last_paid_at->month === $now->month && $b->last_paid_at->year === $now->year);
        })->count();

        return Inertia::render('Finance/Bills', [
            'bills' => $bills,
            'accounts' => $accounts,
            'categories' => $categories,
            'summary' => [
                'total_monthly' => $totalMonthly,
                'total_annual' => $totalAnnual,
                'unpaid_count' => $unpaidCount,
                'total_active' => $bills->where('is_active', true)->count(),
            ],
        ]);
    }

    /**
     * Store a newly created bill.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:bill,subscription'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'recurrence_period' => ['required', 'string', 'in:monthly,annual'],
            'category_id' => ['nullable', 'exists:finance_categories,id'],
            'account_id' => ['nullable', 'exists:finance_accounts,id'],
        ]);

        FinanceBill::create($validated);

        return redirect()->back()->with('success', 'Tagihan berhasil didaftarkan.');
    }

    /**
     * Update the specified bill.
     */
    public function update(Request $request, FinanceBill $bill): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:bill,subscription'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'recurrence_period' => ['required', 'string', 'in:monthly,annual'],
            'category_id' => ['nullable', 'exists:finance_categories,id'],
            'account_id' => ['nullable', 'exists:finance_accounts,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        $bill->update($validated);

        return redirect()->back()->with('success', 'Tagihan berhasil diubah.');
    }

    /**
     * Remove the specified bill.
     */
    public function destroy(FinanceBill $bill): RedirectResponse
    {
        $bill->delete();

        return redirect()->back()->with('success', 'Tagihan berhasil dihapus.');
    }

    /**
     * Record payment for the bill.
     */
    public function pay(Request $request, FinanceBill $bill): RedirectResponse
    {
        $accountId = $request->input('account_id', $bill->account_id);

        if (! $accountId) {
            return redirect()->back()->withErrors(['account_id' => 'Harap tentukan rekening pembayaran.']);
        }

        // Create transaction in ledger
        FinanceTransaction::create([
            'account_id' => $accountId,
            'category_id' => $bill->category_id,
            'type' => 'expense',
            'amount' => $bill->amount,
            'transaction_date' => now()->toDateString(),
            'description' => 'Pembayaran '.($bill->type === 'subscription' ? 'Langganan' : 'Tagihan').': '.$bill->name,
            'tags' => [$bill->type, 'tagihan-otomatis'],
        ]);

        // Update bill last paid status
        $bill->update([
            'last_paid_at' => now()->toDateString(),
        ]);

        return redirect()->back()->with('success', 'Tagihan berhasil dibayar & dicatat di ledger.');
    }

    /**
     * Return payment history for a specific bill from the transactions ledger.
     */
    public function history(FinanceBill $bill): JsonResponse
    {
        $prefix = 'Pembayaran '.($bill->type === 'subscription' ? 'Langganan' : 'Tagihan').': '.$bill->name;

        $transactions = FinanceTransaction::with('account')
            ->where('description', 'like', $prefix.'%')
            ->orderBy('transaction_date', 'desc')
            ->limit(24)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'date' => $t->transaction_date,
                'amount' => $t->amount,
                'currency' => $t->account?->currency ?? $bill->currency,
                'account_name' => $t->account?->name ?? '—',
            ]);

        return response()->json($transactions);
    }
}
