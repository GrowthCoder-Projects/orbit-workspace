<?php

namespace App\Console\Commands;

use App\Models\Habit;
use App\Models\HabitLog;
use App\Notifications\HabitReminderNotification;
use Illuminate\Console\Command;

class SendHabitReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'habits:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send custom daily reminders for incomplete habits to Telegram';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $now = now();
        $nowTime = $now->format('H:i');
        $todayStr = $now->format('Y-m-d');
        $todayDayName = strtolower($now->format('D')); // mon, tue, etc.

        // Get all active habits scheduled for a reminder at this exact minute
        $habits = Habit::where('is_active', true)
            ->where('reminder_time', $nowTime)
            ->get();

        foreach ($habits as $habit) {
            $isScheduledToday = false;

            if ($habit->frequency_type === 'daily') {
                $isScheduledToday = true;
            } elseif ($habit->frequency_type === 'custom_days') {
                $isScheduledToday = in_array($todayDayName, array_map('strtolower', $habit->frequency_days ?? []));
            } elseif ($habit->frequency_type === 'weekly') {
                // For weekly, check if target count is not yet reached this week
                $startOfWeek = $now->copy()->startOfWeek()->format('Y-m-d');
                $endOfWeek = $now->copy()->endOfWeek()->format('Y-m-d');

                $completionsCount = HabitLog::where('habit_id', $habit->id)
                    ->whereBetween('completed_date', [$startOfWeek, $endOfWeek])
                    ->count();

                if ($completionsCount < $habit->frequency_count) {
                    $isScheduledToday = true;
                }
            }

            if ($isScheduledToday) {
                // Check if already completed today
                $alreadyCompleted = HabitLog::where('habit_id', $habit->id)
                    ->where('completed_date', $todayStr)
                    ->exists();

                if (! $alreadyCompleted) {
                    $user = $habit->user;
                    if ($user) {
                        $user->notify(new HabitReminderNotification($habit));
                        $this->info("Sent reminder for habit ID {$habit->id}: {$habit->name}");
                    }
                }
            }
        }
    }
}
