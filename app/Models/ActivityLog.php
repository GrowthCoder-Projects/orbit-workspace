<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'properties',
        'ip_address',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Map of subject_type class names to human-readable module labels.
     *
     * @return array<string, string>
     */
    public static function moduleLabels(): array
    {
        return [
            'App\Models\Project' => 'Projects',
            'App\Models\Task' => 'Tasks',
            'App\Models\Note' => 'Notes',
            'App\Models\Client' => 'Clients',
            'App\Models\Invoice' => 'Invoices',
            'App\Models\FinanceTransaction' => 'Finance',
            'App\Models\FinanceBudget' => 'Finance',
            'App\Models\Bookmark' => 'Bookmarks',
            'App\Models\Document' => 'Documents',
            'App\Models\KbArticle' => 'Knowledge Base',
            'App\Models\CalendarEvent' => 'Calendar',
            'auth' => 'Auth',
        ];
    }

    /**
     * Scope to filter by module (subject_label).
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('subject_label', $module);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeForDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Get the human-readable icon for the action.
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'created' => '✅',
            'updated' => '✏️',
            'deleted' => '🗑️',
            'login' => '🔑',
            'logout' => '🚪',
            'status_changed' => '🔄',
            'budget_alert' => '⚠️',
            default => '📌',
        };
    }
}
