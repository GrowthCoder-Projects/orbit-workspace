<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\ActivityLogService;

class DocumentObserver
{
    public function created(Document $document): void
    {
        ActivityLogService::created($document, $document->name);
    }

    public function updated(Document $document): void
    {
        ActivityLogService::updated($document, $document->name, ['name', 'folder_id']);
    }

    public function deleted(Document $document): void
    {
        ActivityLogService::deleted($document, $document->name);
    }
}
