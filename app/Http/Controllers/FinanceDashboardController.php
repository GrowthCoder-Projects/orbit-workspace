<?php

namespace App\Http\Controllers;

use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceDashboardController extends Controller
{
    /**
     * Display the finance dashboard with aggregations and charts.
     */
    public function index(Request $request): Response
    {
        $accounts = FinanceAccount::orderBy('name')->get();

        // Base currency is IDR. We'll convert USD to IDR at 16000 for aggregate estimations.
        $exchangeRates = [
            'IDR' => 1.0,
            'USD' => 16000.0,
        ];

        // 1. Calculate Net Worth
        $netWorthBreakdown = [];
        $totalNetWorthIDR = 0;

        foreach ($accounts as $account) {
            $currency = $account->currency;
            if (!isset($netWorthBreakdown[$currency])) {
                $netWorthBreakdown[$currency] = 0;
            }
            $netWorthBreakdown[$currency] += $account->balance;

            $rate = $exchangeRates[$currency] ?? 1.0;
            $totalNetWorthIDR += $account->balance * $rate;
        }

        // 2. Current Month Cash Flow (Income vs Expense)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthTransactions = FinanceTransaction::with('account')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->get();

        $monthlyIncomeIDR = 0;
        $monthlyExpenseIDR = 0;

        foreach ($monthTransactions as $tx) {
            $currency = $tx->account->currency ?? 'IDR';
            $rate = $exchangeRates[$currency] ?? 1.0;

            if ($tx->type === 'income') {
                $monthlyIncomeIDR += $tx->amount * $rate;
            } elseif ($tx->type === 'expense') {
                $monthlyExpenseIDR += $tx->amount * $rate;
            }
        }

        // 3. Compute Financial Health Score (0-100)
        $healthScore = 100;
        if ($monthlyIncomeIDR > 0 || $monthlyExpenseIDR > 0) {
            if ($monthlyIncomeIDR === 0.0) {
                $healthScore = 40; // No income but has expenses
            } else {
                $ratio = $monthlyExpenseIDR / $monthlyIncomeIDR;
                if ($ratio <= 0.3) {
                    $healthScore = 95; // Excellent savings rate
                } elseif ($ratio <= 0.6) {
                    $healthScore = 85; // Good savings rate
                } elseif ($ratio <= 1.0) {
                    $healthScore = 70; // Tight budget
                } else {
                    $healthScore = max(10, round(70 - ($ratio - 1) * 30)); // Deficit
                }
            }
        }

        // 4. Past 6 Months Chart Data
        $chartData = $this->getPast6MonthsData($exchangeRates);

        // 5. Recent Transactions
        $recentTransactions = FinanceTransaction::with(['account', 'destinationAccount', 'category'])
            ->latest('transaction_date')
            ->latest('id')
            ->limit(10)
            ->get();

        return Inertia::render('Finance/Dashboard', [
            'accounts' => $accounts,
            'netWorthIDR' => $totalNetWorthIDR,
            'netWorthBreakdown' => $netWorthBreakdown,
            'monthlyIncomeIDR' => $monthlyIncomeIDR,
            'monthlyExpenseIDR' => $monthlyExpenseIDR,
            'healthScore' => $healthScore,
            'chartData' => $chartData,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    /**
     * Get monthly Income vs Expense data for the past 6 months.
     */
    private function getPast6MonthsData(array $exchangeRates): array
    {
        $labels = [];
        $incomeData = [];
        $expenseData = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');

            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $txs = FinanceTransaction::with('account')
                ->whereBetween('transaction_date', [$start, $end])
                ->get();

            $incSum = 0;
            $expSum = 0;

            foreach ($txs as $tx) {
                $currency = $tx->account->currency ?? 'IDR';
                $rate = $exchangeRates[$currency] ?? 1.0;

                if ($tx->type === 'income') {
                    $incSum += $tx->amount * $rate;
                } elseif ($tx->type === 'expense') {
                    $expSum += $tx->amount * $rate;
                }
            }

            $incomeData[] = $incSum;
            $expenseData[] = $expSum;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Pemasukan (IDR)',
                    'data' => $incomeData,
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                ],
                [
                    'label' => 'Pengeluaran (IDR)',
                    'data' => $expenseData,
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#ef4444',
                ]
            ]
        ];
    }
}
