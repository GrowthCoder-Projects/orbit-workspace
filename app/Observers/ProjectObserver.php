<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\ActivityLogService;

class ProjectObserver
{
    public function created(Project $project): void
    {
        ActivityLogService::created($project, $project->name);
    }

    public function updated(Project $project): void
    {
        ActivityLogService::updated($project, $project->name);
    }

    public function deleted(Project $project): void
    {
        ActivityLogService::deleted($project, $project->name);
    }
}
