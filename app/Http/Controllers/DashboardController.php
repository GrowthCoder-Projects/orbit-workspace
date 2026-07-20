<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Client;
use App\Models\FinanceAccount;
use App\Models\FinanceBill;
use App\Models\FinanceTransaction;
use App\Models\Folder;
use App\Models\Invoice;
use App\Models\Note;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Habit;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the workspace dashboard.
     */
    public function index(Request $request): Response
    {
        $now = Carbon::now();
        $todayStr = $now->toDateString();

        // 1. KPI - Active Projects & Pending Tasks count
        $activeProjectsCount = Project::where('status', 'active')->count();
        $pendingTasksCount = Task::whereIn('status', ['todo', 'in_progress', 'blocked'])->count();

        // 2. KPI - Finance Net Worth & Current Month Income/Expense
        $accounts = FinanceAccount::all();
        $exchangeRates = [
            'IDR' => 1.0,
            'USD' => 16000.0,
        ];
        $totalNetWorthIDR = 0;
        foreach ($accounts as $account) {
            $rate = $exchangeRates[$account->currency] ?? 1.0;
            $totalNetWorthIDR += $account->balance * $rate;
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalIncomeThisMonth = FinanceTransaction::where('finance_transactions.type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->join('finance_accounts', 'finance_transactions.account_id', '=', 'finance_accounts.id')
            ->selectRaw('SUM(finance_transactions.amount * CASE WHEN finance_accounts.currency = "USD" THEN 16000 ELSE 1 END) as total')
            ->value('total') ?? 0;

        $totalExpenseThisMonth = FinanceTransaction::where('finance_transactions.type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->join('finance_accounts', 'finance_transactions.account_id', '=', 'finance_accounts.id')
            ->selectRaw('SUM(finance_transactions.amount * CASE WHEN finance_accounts.currency = "USD" THEN 16000 ELSE 1 END) as total')
            ->value('total') ?? 0;

        // 3. KPI - Today's Schedule & Deadlines
        $startOfDay = $now->copy()->startOfDay();
        $endOfDay = $now->copy()->endOfDay();

        // Custom calendar events today
        $events = CalendarEvent::all();
        $todayCalendarEvents = collect();
        foreach ($events as $event) {
            $todayCalendarEvents = $todayCalendarEvents->concat($event->getInstancesInRange($startOfDay, $endOfDay));
        }

        $formattedCalendarEvents = $todayCalendarEvents->map(function ($event) {
            $durationStr = '';
            if (!$event->is_all_day && $event->start_at && $event->end_at) {
                $diffMinutes = $event->start_at->diffInMinutes($event->end_at);
                if ($diffMinutes >= 60) {
                    $hours = round($diffMinutes / 60, 1);
                    $durationStr = $hours . 'j';
                } else {
                    $durationStr = $diffMinutes . 'm';
                }
            }

            return [
                'id' => $event->id,
                'title' => $event->title,
                'type' => 'event',
                'start_time' => $event->is_all_day ? 'Seharian' : $event->start_at->format('H:i'),
                'end_time' => $event->is_all_day ? 'Seharian' : $event->end_at->format('H:i'),
                'is_all_day' => $event->is_all_day,
                'color' => $event->color ?? '#3b82f6',
                'description' => $event->description,
                'duration' => $durationStr,
            ];
        });

        // Tasks due today
        $tasksDueToday = Task::with('project')
            ->whereDate('due_date', $todayStr)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'type' => 'task',
                    'start_time' => 'Tenggat',
                    'end_time' => 'Hari Ini',
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'project_name' => $task->project->name ?? null,
                    'project_color' => $task->project->color ?? null,
                    'duration' => '',
                ];
            });

        // Milestones due today
        $milestonesDueToday = ProjectMilestone::with('project')
            ->whereDate('due_date', $todayStr)
            ->get()
            ->map(function ($milestone) {
                return [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'type' => 'milestone',
                    'start_time' => 'Milestone',
                    'end_time' => 'Hari Ini',
                    'status' => $milestone->status,
                    'project_name' => $milestone->project->name ?? null,
                    'project_color' => $milestone->project->color ?? null,
                    'duration' => '',
                ];
            });

        // Merge and count
        $todaySchedule = $formattedCalendarEvents
            ->concat($tasksDueToday)
            ->concat($milestonesDueToday)
            ->sortBy('start_time')
            ->values();

        $todayEventsCount = $todaySchedule->count();

        // 4. Active Tasks Section
        $activeTasks = Task::with(['project', 'checklists'])
            ->whereIn('status', ['todo', 'in_progress', 'blocked'])
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 1 WHEN priority = 'high' THEN 2 WHEN priority = 'medium' THEN 3 WHEN priority = 'low' THEN 4 ELSE 5 END")
            ->orderBy('due_date', 'asc')
            ->limit(4) // Show up to 4 like the reference
            ->get()
            ->map(function ($task) {
                $total = $task->checklists->count();
                $completed = $task->checklists->where('is_completed', true)->count();
                $task->checklist_progress = $total > 0 ? "($completed/$total)" : '';
                return $task;
            });

        // 5. Weekly Chart Data (Completed tasks & mock focus hours)
        $startOfWeek = Carbon::now()->startOfWeek();
        $weeklyChartData = [
            'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            'completedTasks' => [],
            'focusHours' => [],
        ];

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $tasksCompleted = Task::where('status', 'done')
                ->whereDate('completed_at', $date->toDateString())
                ->count();
            
            $weeklyChartData['completedTasks'][] = $tasksCompleted;
            // Focus time simulation: completed tasks * 1.5h with a base of random hours if none completed
            $weeklyChartData['focusHours'][] = $tasksCompleted > 0 ? ($tasksCompleted * 1.5) : rand(1, 4);
        }

        // 6. Expense Chart Data (Past 30 days)
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

            if (!isset($categorySums[$catName])) {
                $categorySums[$catName] = [
                    'amount' => 0.0,
                    'color' => $catColor,
                ];
            }
            $categorySums[$catName]['amount'] += (float) ($tx->amount * $rate);
        }
        uasort($categorySums, fn($a, $b) => $b['amount'] <=> $a['amount']);

        $expenseChartData = [
            'labels' => array_keys($categorySums),
            'datasets' => [
                [
                    'data' => array_column($categorySums, 'amount'),
                    'backgroundColor' => array_column($categorySums, 'color'),
                ]
            ]
        ];

        // 7. Upcoming Bills
        $upcomingBills = FinanceBill::with(['category', 'account'])
            ->where('is_active', true)
            ->get()
            ->filter(function ($b) use ($now) {
                if (!$b->last_paid_at) {
                    return true;
                }
                return !($b->last_paid_at->month === $now->month && $b->last_paid_at->year === $now->year);
            })
            ->values()
            ->take(5);

        // 7. Finance Trend Chart Data (Past 30 days expenses)
        $dailyExpenses = FinanceTransaction::where('type', 'expense')
            ->where('transaction_date', '>=', $past30Days)
            ->groupBy('transaction_date')
            ->selectRaw('transaction_date, SUM(amount) as total')
            ->orderBy('transaction_date', 'asc')
            ->get();

        $financeTrendData = [];
        $financeTrendLabels = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStr = $date->toDateString();
            $financeTrendLabels[] = $date->format('j M');
            $financeTrendData[] = (float) ($dailyExpenses->where('transaction_date', $dateStr)->first()->total ?? 0);
        }

        $financeTrendChartData = [
            'labels' => $financeTrendLabels,
            'datasets' => [
                [
                    'data' => $financeTrendData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                ]
            ]
        ];

        // 8. Invoice Stats (Sent, Paid, Overdue)
        $invoiceStats = [
            'paid' => [
                'count' => Invoice::where('status', 'paid')->count(),
                'total' => Invoice::where('status', 'paid')->sum('total'),
            ],
            'sent' => [
                'count' => Invoice::where('status', 'sent')->count(),
                'total' => Invoice::where('status', 'sent')->sum('total'),
            ],
            'overdue' => [
                'count' => Invoice::where('status', 'overdue')->count(),
                'total' => Invoice::where('status', 'overdue')->sum('total'),
            ],
        ];

        // 9. Quick Draft Note & Recent Notes
        $quickDraftNote = Note::where('title', 'Quick Draft')->first();
        if (!$quickDraftNote) {
            $quickDraftNote = Note::create([
                'title' => 'Quick Draft',
                'content' => '<p>Mulai menulis ide Anda di sini...</p>',
                'is_favorite' => false,
                'is_archived' => false,
            ]);
        }

        $recentNotes = Note::where('title', '!=', 'Quick Draft')
            ->where('is_archived', false)
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($note) {
                // strip HTML tags for preview text
                $note->preview = trim(strip_tags($note->content));
                if (strlen($note->preview) > 60) {
                    $note->preview = substr($note->preview, 0, 60) . '...';
                }
                return $note;
            });

        // 10. Metadata dropdown for Modals
        $projects = Project::with('milestones')->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        $folders = Folder::orderBy('name')->get();
        $nextInvoiceNumber = Invoice::generateNextNumber();

        $todayHabits = Habit::where('is_active', true)
            ->with(['logs' => function ($query) {
                $query->whereDate('completed_date', Carbon::today());
            }])
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'activeProjectsCount' => $activeProjectsCount,
                'pendingTasksCount' => $pendingTasksCount,
                'netWorthIDR' => $totalNetWorthIDR,
                'todayEventsCount' => $todayEventsCount,
                'totalIncomeThisMonth' => (float) $totalIncomeThisMonth,
                'totalExpenseThisMonth' => (float) $totalExpenseThisMonth,
            ],
            'todaySchedule' => $todaySchedule,
            'activeTasks' => $activeTasks,
            'weeklyChartData' => $weeklyChartData,
            'expenseChartData' => $expenseChartData,
            'financeTrendChartData' => $financeTrendChartData,
            'upcomingBills' => $upcomingBills,
            'invoiceStats' => $invoiceStats,
            'quickDraftNote' => $quickDraftNote,
            'recentNotes' => $recentNotes,
            'projects' => $projects,
            'clients' => $clients,
            'folders' => $folders,
            'nextInvoiceNumber' => $nextInvoiceNumber,
            'todayHabits' => $todayHabits,
        ]);
    }
}
