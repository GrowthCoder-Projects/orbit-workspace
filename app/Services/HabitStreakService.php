<?php

namespace App\Services;

use App\Models\Habit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HabitStreakService
{
    /**
     * Recalculate current and longest streaks for a habit.
     */
    public function recalculate(Habit $habit): void
    {
        // Load all logs sorted by completed_date descending
        $logs = $habit->logs()->orderBy('completed_date', 'desc')->get();

        if ($logs->isEmpty()) {
            $habit->update([
                'streak_current' => 0,
                'streak_longest' => 0,
            ]);
            return;
        }

        $streakCurrent = 0;
        $streakLongest = 0;

        switch ($habit->frequency_type) {
            case 'daily':
                [$streakCurrent, $streakLongest] = $this->calculateDailyStreak($logs);
                break;
            case 'custom_days':
                [$streakCurrent, $streakLongest] = $this->calculateCustomDaysStreak($habit, $logs);
                break;
            case 'weekly':
                [$streakCurrent, $streakLongest] = $this->calculateWeeklyStreak($habit, $logs);
                break;
        }

        $habit->update([
            'streak_current' => $streakCurrent,
            'streak_longest' => max($streakLongest, $habit->streak_longest, $streakCurrent),
        ]);
    }

    /**
     * Calculate streaks for daily habits.
     */
    private function calculateDailyStreak(Collection $logs): array
    {
        $logDates = $logs->pluck('completed_date')->map(fn($date) => $date->format('Y-m-d'))->toArray();
        $logDatesSet = array_flip($logDates);

        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        // Current Streak
        $currentStreak = 0;
        $checkDate = Carbon::today();

        // If today is not completed and yesterday is not completed, streak is 0
        if (!isset($logDatesSet[$today]) && !isset($logDatesSet[$yesterday])) {
            $currentStreak = 0;
        } else {
            // Start checking from the most recent completed date (today or yesterday)
            $checkDate = isset($logDatesSet[$today]) ? Carbon::today() : Carbon::yesterday();
            while (isset($logDatesSet[$checkDate->format('Y-m-d')])) {
                $currentStreak++;
                $checkDate->subDay();
            }
        }

        // Longest Streak
        $longestStreak = 0;
        $tempStreak = 0;
        
        // Sort dates ascending to count longest streak
        $sortedDates = $logs->pluck('completed_date')->map(fn($date) => Carbon::parse($date))->sort()->values();
        
        if ($sortedDates->isNotEmpty()) {
            $tempStreak = 1;
            $longestStreak = 1;
            for ($i = 1; $i < $sortedDates->count(); $i++) {
                $diff = $sortedDates[$i]->diffInDays($sortedDates[$i - 1]);
                if ($diff === 1) {
                    $tempStreak++;
                } elseif ($diff > 1) {
                    $longestStreak = max($longestStreak, $tempStreak);
                    $tempStreak = 1;
                }
            }
            $longestStreak = max($longestStreak, $tempStreak);
        }

        return [$currentStreak, $longestStreak];
    }

    /**
     * Calculate streaks for custom scheduled days (e.g. ['mon', 'wed', 'fri']).
     */
    private function calculateCustomDaysStreak(Habit $habit, Collection $logs): array
    {
        $scheduledDays = array_map('strtolower', $habit->frequency_days ?? []);
        if (empty($scheduledDays)) {
            return [0, 0];
        }

        $logDates = $logs->pluck('completed_date')->map(fn($date) => $date->format('Y-m-d'))->toArray();
        $logDatesSet = array_flip($logDates);

        // Helper to check if a day of week is scheduled
        $isScheduled = function (Carbon $date) use ($scheduledDays) {
            $dayName = strtolower($date->format('D')); // mon, tue, etc.
            return in_array($dayName, $scheduledDays);
        };

        // 1. Current Streak calculation
        $currentStreak = 0;
        $checkDate = Carbon::today();

        // Find the last scheduled date (including today)
        while (!$isScheduled($checkDate)) {
            $checkDate->subDay();
        }

        // If the last scheduled date is TODAY, but not logged, we also check the previous scheduled day
        $lastScheduledDate = $checkDate->copy();
        if ($lastScheduledDate->isToday() && !isset($logDatesSet[$lastScheduledDate->format('Y-m-d')])) {
            // Today is scheduled but not logged.
            // Check if there was any prior logging. We move to the previous scheduled day to see if streak is still alive.
            $prevScheduled = $lastScheduledDate->copy()->subDay();
            while (!$isScheduled($prevScheduled)) {
                $prevScheduled->subDay();
            }
            if (!isset($logDatesSet[$prevScheduled->format('Y-m-d')])) {
                // Previous scheduled day is also not logged, streak is broken
                $currentStreak = 0;
            } else {
                // Streak is alive based on the previous scheduled day, start checking from there
                $checkDate = $prevScheduled;
                while (isset($logDatesSet[$checkDate->format('Y-m-d')])) {
                    $currentStreak++;
                    $checkDate->subDay();
                    while (!$isScheduled($checkDate)) {
                        $checkDate->subDay();
                    }
                }
            }
        } else {
            // Start checking from the last scheduled date (which is either today and logged, or yesterday or earlier and logged/unlogged)
            if (!isset($logDatesSet[$lastScheduledDate->format('Y-m-d')])) {
                $currentStreak = 0;
            } else {
                $checkDate = $lastScheduledDate;
                while (isset($logDatesSet[$checkDate->format('Y-m-d')])) {
                    $currentStreak++;
                    $checkDate->subDay();
                    while (!$isScheduled($checkDate)) {
                        $checkDate->subDay();
                    }
                }
            }
        }

        // 2. Longest Streak calculation (consecutive scheduled days completed)
        $longestStreak = 0;
        $tempStreak = 0;

        // Fetch all logs, sort ascending
        $sortedLogs = $logs->pluck('completed_date')->map(fn($date) => Carbon::parse($date))->sort()->values();

        if ($sortedLogs->isNotEmpty()) {
            $tempStreak = 0;
            $longestStreak = 0;

            // We can iterate from the first log date to the last log date day-by-day
            $firstDate = $sortedLogs->first()->copy();
            $lastDate = $sortedLogs->last()->copy();

            $curr = $firstDate;
            while ($curr->lte($lastDate)) {
                if ($isScheduled($curr)) {
                    if (isset($logDatesSet[$curr->format('Y-m-d')])) {
                        $tempStreak++;
                    } else {
                        $longestStreak = max($longestStreak, $tempStreak);
                        $tempStreak = 0;
                    }
                }
                $curr->addDay();
            }
            $longestStreak = max($longestStreak, $tempStreak);
        }

        return [$currentStreak, $longestStreak];
    }

    /**
     * Calculate streaks for weekly habits (e.g. 3 times per week).
     */
    private function calculateWeeklyStreak(Habit $habit, Collection $logs): array
    {
        $targetCount = $habit->frequency_count;

        // Group completions by week key (Year-WeekNumber, e.g., '2026-29')
        // We use ISO week format 'o-W' (o is the ISO year, W is ISO week)
        $completionsByWeek = $logs->groupBy(fn($log) => $log->completed_date->format('o-W'))
            ->map(fn($weekLogs) => $weekLogs->count());

        $currentWeekKey = Carbon::today()->format('o-W');
        $prevWeekKey = Carbon::today()->subWeek()->format('o-W');

        $currentWeekCompleted = ($completionsByWeek->get($currentWeekKey, 0) >= $targetCount);
        $prevWeekCompleted = ($completionsByWeek->get($prevWeekKey, 0) >= $targetCount);

        // Current Streak calculation
        $currentStreak = 0;
        if (!$currentWeekCompleted && !$prevWeekCompleted) {
            $currentStreak = 0;
        } else {
            // Start checking back week-by-week
            $checkWeek = $currentWeekCompleted ? Carbon::today() : Carbon::today()->subWeek();
            while (true) {
                $weekKey = $checkWeek->format('o-W');
                $count = $completionsByWeek->get($weekKey, 0);
                if ($count >= $targetCount) {
                    $currentStreak++;
                    $checkWeek->subWeek();
                } else {
                    break;
                }
            }
        }

        // Longest Streak calculation (consecutive completed weeks)
        $longestStreak = 0;
        $tempStreak = 0;

        if ($completionsByWeek->isNotEmpty()) {
            // Get all unique sorted week keys
            $weeks = $logs->map(fn($log) => Carbon::parse($log->completed_date))
                ->map(fn($date) => $date->startOfWeek())
                ->unique()
                ->sort()
                ->values();

            if ($weeks->isNotEmpty()) {
                $firstWeek = $weeks->first()->copy();
                $lastWeek = $weeks->last()->copy();

                $currWeek = $firstWeek;
                while ($currWeek->lte($lastWeek)) {
                    $weekKey = $currWeek->format('o-W');
                    $count = $completionsByWeek->get($weekKey, 0);

                    if ($count >= $targetCount) {
                        $tempStreak++;
                    } else {
                        $longestStreak = max($longestStreak, $tempStreak);
                        $tempStreak = 0;
                    }
                    $currWeek->addWeek();
                }
                $longestStreak = max($longestStreak, $tempStreak);
            }
        }

        return [$currentStreak, $longestStreak];
    }
}
