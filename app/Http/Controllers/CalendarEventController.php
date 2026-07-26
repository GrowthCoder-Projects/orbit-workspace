<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\ProjectMilestone;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarEventController extends Controller
{
    /**
     * Display a listing of calendar items (events, tasks, milestones).
     */
    public function index(Request $request): Response
    {
        $start = $request->input('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $end = $request->input('end')
            ? Carbon::parse($request->input('end'))->endOfDay()
            : now()->endOfMonth()->endOfDay();

        // 1. Get custom events and expand recurrences
        $events = CalendarEvent::all();
        $expandedEvents = collect();
        foreach ($events as $event) {
            $expandedEvents = $expandedEvents->concat($event->getInstancesInRange($start, $end));
        }

        // 2. Get tasks with due dates in range
        $tasks = Task::with('project')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        // 3. Get milestones with due dates in range
        $milestones = ProjectMilestone::with('project')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return Inertia::render('Calendar/Index', [
            'events' => $expandedEvents,
            'tasks' => $tasks,
            'milestones' => $milestones,
            'notifications' => $request->user()->notifications()->latest()->take(10)->get(),
            'filters' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
        ]);
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after_or_equal:start_at',
            'is_all_day' => 'boolean',
            'color' => 'nullable|string|max:7',
            'recurrence_pattern' => 'nullable|string|in:none,daily,weekly,monthly',
            'recurrence_end' => 'nullable|date|after_or_equal:start_at',
            'reminder_lead_time' => 'nullable|integer|in:0,5,15,30,60,120,1440',
        ]);

        CalendarEvent::create($validated);

        return redirect()->route('calendar.index')->with('success', 'Event created successfully.');
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, CalendarEvent $event): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after_or_equal:start_at',
            'is_all_day' => 'boolean',
            'color' => 'nullable|string|max:7',
            'recurrence_pattern' => 'nullable|string|in:none,daily,weekly,monthly',
            'recurrence_end' => 'nullable|date|after_or_equal:start_at',
            'reminder_lead_time' => 'nullable|integer|in:0,5,15,30,60,120,1440',
        ]);

        // If recurrence pattern changes, reset reminder_sent_at so new notifications can fire
        if ($event->recurrence_pattern !== ($validated['recurrence_pattern'] ?? null) ||
            $event->start_at->ne(Carbon::parse($validated['start_at']))) {
            $validated['reminder_sent_at'] = null;
        }

        $event->update($validated);

        return redirect()->route('calendar.index')->with('success', 'Event updated successfully.');
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(CalendarEvent $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('calendar.index')->with('success', 'Event deleted successfully.');
    }
}
