<?php

namespace App\Observers;

use App\Models\Bookmark;
use App\Services\ActivityLogService;

class BookmarkObserver
{
    public function created(Bookmark $bookmark): void
    {
        ActivityLogService::created($bookmark, $bookmark->title ?? $bookmark->url);
    }

    public function updated(Bookmark $bookmark): void
    {
        ActivityLogService::updated($bookmark, $bookmark->title ?? $bookmark->url, ['title', 'url', 'category_id', 'is_favorite']);
    }

    public function deleted(Bookmark $bookmark): void
    {
        ActivityLogService::deleted($bookmark, $bookmark->title ?? $bookmark->url);
    }
}
