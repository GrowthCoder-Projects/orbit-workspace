<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\ActivityLogService;

class TaskObserver
{
    public function created(Task $task): void
    {
        ActivityLogService::created($task, $task->title);
    }

    public function updated(Task $task): void
    {
        // Detect status change separately for Telegram notification
        if ($task->isDirty('status')) {
            $from = $task->getOriginal('status');
            $to = $task->status;

            ActivityLogService::statusChanged($task, $task->title, $from, $to);

            // Send Telegram notification for task status changes
            $user = auth()->user();
            if ($user) {
                $user->notify(new \App\Notifications\ActivityNotification(
                    title: "🔄 Task Status Berubah",
                    body: "Task \"{$task->title}\" berubah dari *{$from}* ke *{$to}*",
                ));
            }

            return;
        }

        ActivityLogService::updated($task, $task->title);
    }

    public function deleted(Task $task): void
    {
        ActivityLogService::deleted($task, $task->title);
    }
}
