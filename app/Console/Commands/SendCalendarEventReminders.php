<?php

namespace App\Console\Commands;

use App\Models\CalendarEvent;
use App\Notifications\CalendarEventReminderNotification;
use Illuminate\Console\Command;

class SendCalendarEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send calendar event reminder notifications to users';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $now = now();
        // Look ahead 24 hours to find any upcoming events with reminders
        $lookAheadEnd = $now->copy()->addDay();

        // Get all events with reminders configured
        $events = CalendarEvent::whereNotNull('reminder_lead_time')->get();

        foreach ($events as $event) {
            // Get instances within the look ahead window
            $instances = $event->getInstancesInRange($now, $lookAheadEnd);

            foreach ($instances as $instance) {
                $leadTime = $event->reminder_lead_time;
                $triggerTime = $instance->start_at->copy()->subMinutes($leadTime);

                // If trigger time is in the past (or now) and the event hasn't started yet
                if ($now->gte($triggerTime) && $now->lt($instance->start_at)) {
                    // Check if a reminder for this instance has already been sent
                    $alreadySent = $event->reminder_sent_at && $event->reminder_sent_at->gte($triggerTime);

                    if (! $alreadySent) {
                        $user = $event->user;
                        if ($user) {
                            $user->notify(new CalendarEventReminderNotification($instance));
                        }

                        // Mark as sent
                        $event->reminder_sent_at = $now;
                        $event->save();

                        $this->info("Sent reminder for event ID {$event->id}: {$event->title} (instance starting at {$instance->start_at})");
                    }
                }
            }
        }
    }
}
