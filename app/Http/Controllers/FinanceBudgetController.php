<?php

namespace App\Http\Controllers;

use App\Models\FinanceBudget;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceBudgetController extends Controller
{
    /**
     * Display a listing of budgets.
     */
    public function index(Request $request): Response
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $categories = FinanceCategory::where('type', 'expense')->orderBy('name')->get();
        $budgets = FinanceBudget::where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('category_id');

        // Calculate actual spending in that month/year
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $spendings = FinanceTransaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $budgetData = $categories->map(function ($cat) use ($budgets, $spendings) {
            $budget = $budgets->get($cat->id);
            return [
                'category_id' => $cat->id,
                'category_name' => $cat->name,
                'category_color' => $cat->color,
                'category_icon' => $cat->icon,
                'budget_id' => $budget ? $budget->id : null,
                'budget_amount' => $budget ? $budget->amount : 0.00,
                'actual_spent' => $spendings->get($cat->id) ?? 0.00,
            ];
        });

        return Inertia::render('Finance/Budgets', [
            'budgets' => $budgetData,
            'selectedYear' => (int) $year,
            'selectedMonth' => (int) $month,
            'categories' => $categories,
        ]);
    }

    /**
     * Store or update a category budget.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:finance_categories,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        FinanceBudget::updateOrCreate(
            [
                'category_id' => $validated['category_id'],
                'period' => 'monthly',
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'amount' => $validated['amount'],
            ]
        );

        return redirect()->back()->with('success', 'Anggaran berhasil disimpan.');
    }

    /**
     * Remove the specified budget.
     */
    public function destroy(FinanceBudget $budget): RedirectResponse
    {
        $budget->delete();

        return redirect()->back()->with('success', 'Anggaran berhasil dihapus.');
    }
}
