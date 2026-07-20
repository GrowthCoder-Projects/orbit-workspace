<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'slug',
        'description',
        'color',
        'repository_url',
        'production_url',
        'staging_url',
        'server_ip',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(static function (Project $project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });

        static::updating(static function (Project $project) {
            if ($project->isDirty('name') && ! $project->isDirty('slug')) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    /**
     * Get the milestones for the project.
     *
     * @return HasMany<ProjectMilestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sort_order')->orderBy('due_date');
    }

    /**
     * Get the tasks for the project.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the client that owns the project.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the project's production URL dynamically if it is the current workspace.
     */
    public function getProductionUrlAttribute(?string $value): ?string
    {
        if ($this->slug === 'workspace-os') {
            return url('/app');
        }

        return $value;
    }

    /**
     * Get the project's staging URL dynamically if it is the current workspace.
     */
    public function getStagingUrlAttribute(?string $value): ?string
    {
        if ($this->slug === 'workspace-os') {
            return url('/app');
        }

        return $value;
    }

    /**
     * Calculate the progress percentage based on completed tasks.
     */
    public function getProgressPercentAttribute(): int
    {
        $total = $this->tasks()->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->tasks()->where('status', 'done')->count();

        return (int) round(($done / $total) * 100);
    }
}
