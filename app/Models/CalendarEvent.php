<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CalendarEvent extends Model
{
    use BelongsToUser;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'is_all_day',
        'color',
        'recurrence_pattern',
        'recurrence_end',
        'reminder_lead_time',
        'reminder_sent_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_all_day' => 'boolean',
        'recurrence_end' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'reminder_lead_time' => 'integer',
    ];

    /**
     * Get all instances/occurrences of this event within a date range.
     * For non-recurring events, it returns the event itself if it overlaps.
     * For recurring events, it calculates and returns cloned instances with adjusted dates.
     */
    public function getInstancesInRange(\Carbon\CarbonInterface $rangeStart, \Carbon\CarbonInterface $rangeEnd): Collection
    {
        $instances = collect();

        // If no recurrence pattern, check if it overlaps with range
        if (empty($this->recurrence_pattern) || $this->recurrence_pattern === 'none') {
            if ($this->start_at->lte($rangeEnd) && $this->end_at->gte($rangeStart)) {
                $instances->push(clone $this);
            }
            return $instances;
        }

        // For recurring events
        $currentStart = $this->start_at->copy();
        $durationInSeconds = $this->start_at->diffInSeconds($this->end_at);
        $recurrenceLimit = $this->recurrence_end 
            ? $this->recurrence_end->copy()->endOfDay() 
            : $rangeEnd->copy()->endOfDay();

        // Safety limit to prevent infinite loops if start date is after end date or similar
        $maxIterations = 1000;
        $iteration = 0;

        while ($currentStart->lte($recurrenceLimit) && $currentStart->lte($rangeEnd) && $iteration < $maxIterations) {
            $iteration++;
            $currentEnd = $currentStart->copy()->addSeconds($durationInSeconds);

            // Check if this instance overlaps with the requested range
            if ($currentStart->lte($rangeEnd) && $currentEnd->gte($rangeStart)) {
                $instance = clone $this;
                $instance->start_at = $currentStart->copy();
                $instance->end_at = $currentEnd->copy();
                // Flag to identify it as a recurring instance on the frontend
                $instance->is_recurring_instance = true;
                $instances->push($instance);
            }

            // Move to next occurrence based on pattern
            switch ($this->recurrence_pattern) {
                case 'daily':
                    $currentStart = $currentStart->addDay();
                    break;
                case 'weekly':
                    $currentStart = $currentStart->addWeek();
                    break;
                case 'monthly':
                    $currentStart = $currentStart->addMonth();
                    break;
                default:
                    // Stop loop if pattern is unrecognized
                    break 2;
            }
        }

        return $instances;
    }
}
