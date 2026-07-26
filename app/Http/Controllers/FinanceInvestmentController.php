<?php

namespace App\Http\Controllers;

use App\Models\FinanceAccount;
use App\Models\FinanceInvestment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceInvestmentController extends Controller
{
    /**
     * Display a listing of investments.
     */
    public function index(): Response
    {
        $investments = FinanceInvestment::with('account')->orderBy('name')->get();
        $accounts = FinanceAccount::orderBy('name')->get();

        $portfolioData = $investments->map(function ($inv) {
            $buyValue = $inv->average_buy_price * $inv->shares_quantity;
            $currentValue = $inv->current_price * $inv->shares_quantity;
            $profitLoss = $currentValue - $buyValue;

            $roi = 0.00;
            if ($buyValue > 0) {
                $roi = ($profitLoss / $buyValue) * 100;
            }

            return [
                'id' => $inv->id,
                'name' => $inv->name,
                'type' => $inv->type,
                'account_id' => $inv->account_id,
                'account_name' => $inv->account->name ?? 'Unknown',
                'shares_quantity' => $inv->shares_quantity,
                'average_buy_price' => $inv->average_buy_price,
                'current_price' => $inv->current_price,
                'currency' => $inv->currency,
                'buy_value' => $buyValue,
                'current_value' => $currentValue,
                'profit_loss' => $profitLoss,
                'roi' => $roi,
            ];
        });

        // Exchange rates (simple estimation USD -> IDR)
        $rate = 16000.0;

        $totalPortfolioIDR = 0;
        $totalProfitLossIDR = 0;

        foreach ($portfolioData as $item) {
            $conv = ($item['currency'] === 'USD') ? $rate : 1.0;
            $totalPortfolioIDR += $item['current_value'] * $conv;
            $totalProfitLossIDR += $item['profit_loss'] * $conv;
        }

        return Inertia::render('Finance/Investments', [
            'investments' => $portfolioData,
            'accounts' => $accounts,
            'totalPortfolioValue' => $totalPortfolioIDR,
            'totalProfitLoss' => $totalProfitLossIDR,
        ]);
    }

    /**
     * Store a newly created investment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'exists:finance_accounts,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:stock,mutual_fund,gold,crypto,bond,deposit'],
            'shares_quantity' => ['required', 'numeric', 'min:0.000001'],
            'average_buy_price' => ['required', 'numeric', 'min:0.01'],
            'current_price' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        FinanceInvestment::create($validated);

        return redirect()->back()->with('success', 'Investasi berhasil dicatat.');
    }

    /**
     * Update the specified investment.
     */
    public function update(Request $request, FinanceInvestment $investment): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'exists:finance_accounts,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:stock,mutual_fund,gold,crypto,bond,deposit'],
            'shares_quantity' => ['required', 'numeric', 'min:0.000001'],
            'average_buy_price' => ['required', 'numeric', 'min:0.01'],
            'current_price' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $investment->update($validated);

        return redirect()->back()->with('success', 'Investasi berhasil diubah.');
    }

    /**
     * Remove the specified investment.
     */
    public function destroy(FinanceInvestment $investment): RedirectResponse
    {
        $investment->delete();

        return redirect()->back()->with('success', 'Investasi berhasil dihapus.');
    }
}
