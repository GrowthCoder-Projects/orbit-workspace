<?php

namespace App\Observers;

use App\Models\CalendarEvent;
use App\Services\ActivityLogService;

class CalendarEventObserver
{
    public function created(CalendarEvent $event): void
    {
        ActivityLogService::created($event, $event->title);
    }

    public function updated(CalendarEvent $event): void
    {
        ActivityLogService::updated($event, $event->title, ['title', 'start_at', 'end_at', 'is_all_day', 'color', 'recurrence_rule']);
    }

    public function deleted(CalendarEvent $event): void
    {
        ActivityLogService::deleted($event, $event->title);
    }
}
