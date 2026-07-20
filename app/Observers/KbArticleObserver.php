<?php

namespace App\Observers;

use App\Models\KbArticle;
use App\Services\ActivityLogService;

class KbArticleObserver
{
    public function created(KbArticle $article): void
    {
        ActivityLogService::created($article, $article->title);
    }

    public function updated(KbArticle $article): void
    {
        // Skip logging content-only updates to reduce noise
        if ($article->isDirty('content') && ! $article->isDirty('title')) {
            return;
        }

        ActivityLogService::updated($article, $article->title, ['title', 'slug', 'is_published']);
    }

    public function deleted(KbArticle $article): void
    {
        ActivityLogService::deleted($article, $article->title);
    }
}
