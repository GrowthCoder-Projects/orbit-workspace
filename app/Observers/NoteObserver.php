<?php

namespace App\Observers;

use App\Models\Note;
use App\Services\ActivityLogService;

class NoteObserver
{
    public function created(Note $note): void
    {
        ActivityLogService::created($note, $note->title);
    }

    public function updated(Note $note): void
    {
        // Skip logging auto-save of content to avoid noise
        if ($note->isDirty('content') && ! $note->isDirty('title')) {
            return;
        }

        ActivityLogService::updated($note, $note->title, ['title', 'is_favorite', 'is_archived', 'folder_id']);
    }

    public function deleted(Note $note): void
    {
        ActivityLogService::deleted($note, $note->title);
    }
}
