<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Habit;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;

class DailyWelcomeService
{
    public function __construct(
        protected DailyQuoteService $quoteService
    ) {}

    /**
     * Get complete daily welcome payload for a given user.
     *
     * @return array<string, mixed>
     */
    public function getDailyWelcomeData(User $user): array
    {
        $now = Carbon::now();
        $todayStr = $now->toDateString();

        // 1. Time-based Greeting
        $hour = (int) $now->format('H');
        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Selamat Pagi';
            $icon = 'sun';
            $submessage = 'Siap untuk mencapai target luar biasa hari ini?';
        } elseif ($hour >= 12 && $hour < 15) {
            $greeting = 'Selamat Siang';
            $icon = 'sun-medium';
            $submessage = 'Tetap semangat dan pertahankan fokus produktivitasmu!';
        } elseif ($hour >= 15 && $hour < 18.5) {
            $greeting = 'Selamat Sore';
            $icon = 'sunset';
            $submessage = 'Hampir menyelesaikan hari ini dengan baik, selesaikan tugas utamamu!';
        } else {
            $greeting = 'Selamat Malam';
            $icon = 'moon';
            $submessage = 'Waktu yang tepat untuk mereview pencapaian hari ini.';
        }

        // 2. Formatted Date
        // Set locale to Indonesian if available
        $dateFormatted = $now->translatedFormat('l, d F Y');

        // 3. Quote of the day
        $quote = $this->quoteService->getQuoteOfTheDay();

        // 4. Habits for today
        $todayHabits = Habit::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->with(['logs' => function ($query) use ($todayStr) {
                $query->whereDate('completed_date', $todayStr);
            }])
            ->get()
            ->map(function ($habit) {
                $isCompleted = $habit->logs->isNotEmpty();

                return [
                    'id' => $habit->id,
                    'name' => $habit->name,
                    'description' => $habit->description,
                    'color_accent' => $habit->color_accent ?? '#3b82f6',
                    'streak_current' => $habit->streak_current,
                    'is_completed' => $isCompleted,
                ];
            });

        $habitsCompletedCount = $todayHabits->where('is_completed', true)->count();
        $totalHabitsToday = $todayHabits->count();

        // 5. Urgent Tasks due today (or overdue)
        $tasksDueToday = Task::with('project')
            ->whereIn('status', ['todo', 'in_progress', 'blocked'])
            ->whereDate('due_date', '<=', $todayStr)
            ->orderByRaw("CASE WHEN due_date < '{$todayStr}' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 1 WHEN priority = 'high' THEN 2 WHEN priority = 'medium' THEN 3 ELSE 4 END")
            ->limit(5)
            ->get()
            ->map(function ($task) use ($todayStr) {
                $isOverdue = $task->due_date && $task->due_date->toDateString() < $todayStr;

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'due_date_formatted' => $task->due_date ? $task->due_date->format('d M') : null,
                    'is_overdue' => $isOverdue,
                    'project_name' => $task->project->name ?? null,
                    'project_color' => $task->project->color ?? '#64748b',
                ];
            });

        // 6. Calendar Events today
        $startOfDay = $now->copy()->startOfDay();
        $endOfDay = $now->copy()->endOfDay();

        $events = CalendarEvent::where('user_id', $user->id)->get();
        $todayCalendarEvents = collect();
        foreach ($events as $event) {
            $todayCalendarEvents = $todayCalendarEvents->concat($event->getInstancesInRange($startOfDay, $endOfDay));
        }

        $formattedCalendarEvents = $todayCalendarEvents->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start_time' => $event->is_all_day ? 'Seharian' : $event->start_at->format('H:i'),
                'end_time' => $event->is_all_day ? 'Seharian' : $event->end_at->format('H:i'),
                'color' => $event->color ?? '#3b82f6',
            ];
        })->values();

        return [
            'greeting' => $greeting,
            'greeting_icon' => $icon,
            'submessage' => $submessage,
            'date_formatted' => ucfirst($dateFormatted),
            'user_name' => $user->name,
            'quote' => $quote,
            'summary' => [
                'habits_completed_count' => $habitsCompletedCount,
                'total_habits_today' => $totalHabitsToday,
                'pending_tasks_count' => $tasksDueToday->count(),
                'events_today_count' => $formattedCalendarEvents->count(),
            ],
            'habits' => $todayHabits,
            'tasks' => $tasksDueToday,
            'events' => $formattedCalendarEvents,
        ];
    }
}
