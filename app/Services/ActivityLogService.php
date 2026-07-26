<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log a model CRUD activity.
     *
     * @param  array<array{field: string, old: mixed, new: mixed}>  $properties
     */
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
    ): ActivityLog {
        $subjectType = null;
        $subjectId = null;
        $subjectLabel = null;

        if ($subject !== null) {
            $subjectType = get_class($subject);
            $subjectId = $subject->getKey();
            $subjectLabel = ActivityLog::moduleLabels()[$subjectType] ?? class_basename($subject);
        }

        return ActivityLog::create([
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'description' => $description,
            'properties' => empty($properties) ? null : $properties,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Log a model creation event.
     */
    public static function created(Model $subject, string $name): ActivityLog
    {
        return self::log(
            action: 'created',
            description: class_basename($subject)." \"{$name}\" dibuat",
            subject: $subject,
        );
    }

    /**
     * Log a model update event with a diff of changed fields.
     *
     * @param  list<string>  $watch  Fields to include in diff (empty = all dirty fields)
     */
    public static function updated(Model $subject, string $name, array $watch = []): ActivityLog
    {
        $dirty = $subject->getDirty();
        $original = $subject->getOriginal();

        $fieldsToWatch = empty($watch) ? array_keys($dirty) : array_intersect(array_keys($dirty), $watch);

        // Exclude noisy/internal fields
        $excluded = ['updated_at', 'created_at', 'slug', 'balance'];
        $fieldsToWatch = array_diff($fieldsToWatch, $excluded);

        $properties = [];
        foreach ($fieldsToWatch as $field) {
            $properties[] = [
                'field' => $field,
                'old' => $original[$field] ?? null,
                'new' => $dirty[$field] ?? null,
            ];
        }

        return self::log(
            action: 'updated',
            description: class_basename($subject)." \"{$name}\" diperbarui",
            subject: $subject,
            properties: $properties,
        );
    }

    /**
     * Log a model deletion event.
     */
    public static function deleted(Model $subject, string $name): ActivityLog
    {
        return self::log(
            action: 'deleted',
            description: class_basename($subject)." \"{$name}\" dihapus",
            subject: $subject,
        );
    }

    /**
     * Log a status change event.
     */
    public static function statusChanged(Model $subject, string $name, string $from, string $to): ActivityLog
    {
        return self::log(
            action: 'status_changed',
            description: class_basename($subject)." \"{$name}\" status berubah dari \"{$from}\" ke \"{$to}\"",
            subject: $subject,
            properties: [['field' => 'status', 'old' => $from, 'new' => $to]],
        );
    }

    /**
     * Log a budget over-alert event.
     */
    public static function budgetAlert(Model $subject, string $categoryName, float $spent, float $budget): ActivityLog
    {
        return self::log(
            action: 'budget_alert',
            description: "Budget \"{$categoryName}\" terlampaui: Rp ".number_format($spent, 0, ',', '.').' / Rp '.number_format($budget, 0, ',', '.'),
            subject: $subject,
            properties: [['field' => 'spent', 'old' => $budget, 'new' => $spent]],
        );
    }
}
