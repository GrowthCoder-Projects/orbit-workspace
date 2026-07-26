<?php

namespace App\Http\Controllers;

use App\Models\FinanceTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceReportController extends Controller
{
    /**
     * Display finance reports and analytics.
     */
    public function index(Request $request): Response
    {
        $year = $request->input('year', Carbon::now()->year);

        // Exchange rates (simple estimation USD -> IDR)
        $exchangeRates = [
            'IDR' => 1.0,
            'USD' => 16000.0,
        ];

        // 1. Monthly Income vs Expense for the selected Year
        $monthlyIncome = array_fill(0, 12, 0.0);
        $monthlyExpense = array_fill(0, 12, 0.0);

        $txsThisYear = FinanceTransaction::with('account')
            ->whereYear('transaction_date', $year)
            ->get();

        foreach ($txsThisYear as $tx) {
            $monthIndex = Carbon::parse($tx->transaction_date)->month - 1;
            $currency = $tx->account->currency ?? 'IDR';
            $rate = $exchangeRates[$currency] ?? 1.0;

            if ($tx->type === 'income') {
                $monthlyIncome[$monthIndex] += (float) ($tx->amount * $rate);
            } elseif ($tx->type === 'expense') {
                $monthlyExpense[$monthIndex] += (float) ($tx->amount * $rate);
            }
        }

        // 2. Spending by Category (Past 30 Days)
        $past30Days = Carbon::now()->subDays(30);
        $categorySpendings = FinanceTransaction::with(['category', 'account'])
            ->where('type', 'expense')
            ->where('transaction_date', '>=', $past30Days)
            ->get();

        $categorySums = [];
        foreach ($categorySpendings as $tx) {
            $catName = $tx->category->name ?? 'Lain-lain';
            $catColor = $tx->category->color ?? '#6b7280';
            $currency = $tx->account->currency ?? 'IDR';
            $rate = $exchangeRates[$currency] ?? 1.0;

            if (! isset($categorySums[$catName])) {
                $categorySums[$catName] = [
                    'amount' => 0.0,
                    'color' => $catColor,
                ];
            }
            $categorySums[$catName]['amount'] += (float) ($tx->amount * $rate);
        }

        // Sort by amount descending
        uasort($categorySums, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        $categoryChartData = [
            'labels' => array_keys($categorySums),
            'datasets' => [
                [
                    'data' => array_column($categorySums, 'amount'),
                    'backgroundColor' => array_column($categorySums, 'color'),
                ],
            ],
        ];

        // 3. Year cash flow aggregation
        $totalYearIncome = array_sum($monthlyIncome);
        $totalYearExpense = array_sum($monthlyExpense);
        $totalYearSavings = $totalYearIncome - $totalYearExpense;

        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $cashFlowChartData = [
            'labels' => $monthLabels,
            'datasets' => [
                [
                    'label' => 'Pemasukan (IDR)',
                    'data' => $monthlyIncome,
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                ],
                [
                    'label' => 'Pengeluaran (IDR)',
                    'data' => $monthlyExpense,
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#ef4444',
                ],
            ],
        ];

        return Inertia::render('Finance/Reports', [
            'cashFlowChartData' => $cashFlowChartData,
            'categoryChartData' => $categoryChartData,
            'totalYearIncome' => $totalYearIncome,
            'totalYearExpense' => $totalYearExpense,
            'totalYearSavings' => $totalYearSavings,
            'selectedYear' => (int) $year,
        ]);
    }
}
