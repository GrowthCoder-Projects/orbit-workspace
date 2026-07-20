<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\HabitLog;
use App\Services\HabitStreakService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class HabitController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected HabitStreakService $streakService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): InertiaResponse
    {
        $habits = Habit::where('is_active', true)
            ->with(['logs' => function ($query) {
                // Fetch logs for the last 365 days for the calendar heatmap
                $query->where('completed_date', '>=', Carbon::today()->subDays(365));
            }])
            ->get();

        $archivedHabits = Habit::where('is_active', false)
            ->with(['logs' => function ($query) {
                $query->where('completed_date', '>=', Carbon::today()->subDays(365));
            }])
            ->get();

        return Inertia::render('Habits/Index', [
            'habits' => $habits,
            'archived_habits' => $archivedHabits,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'frequency_type' => ['required', 'string', 'in:daily,weekly,custom_days'],
            'frequency_days' => ['nullable', 'array'],
            'frequency_days.*' => ['string', 'in:mon,tue,wed,thu,fri,sat,sun'],
            'frequency_count' => ['nullable', 'integer', 'min:1', 'max:7'],
            'color_accent' => ['required', 'string', 'in:emerald,indigo,amber,violet,rose'],
            'reminder_time' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        $habit = Habit::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'frequency_type' => $validated['frequency_type'],
            'frequency_days' => $validated['frequency_days'] ?? null,
            'frequency_count' => $validated['frequency_count'] ?? 1,
            'color_accent' => $validated['color_accent'],
            'reminder_time' => $validated['reminder_time'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Habit created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Habit $habit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'frequency_type' => ['required', 'string', 'in:daily,weekly,custom_days'],
            'frequency_days' => ['nullable', 'array'],
            'frequency_days.*' => ['string', 'in:mon,tue,wed,thu,fri,sat,sun'],
            'frequency_count' => ['nullable', 'integer', 'min:1', 'max:7'],
            'color_accent' => ['required', 'string', 'in:emerald,indigo,amber,violet,rose'],
            'reminder_time' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        $habit->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'frequency_type' => $validated['frequency_type'],
            'frequency_days' => $validated['frequency_days'] ?? null,
            'frequency_count' => $validated['frequency_count'] ?? 1,
            'color_accent' => $validated['color_accent'],
            'reminder_time' => $validated['reminder_time'] ?? null,
        ]);

        // Recalculate streak in case frequency settings changed
        $this->streakService->recalculate($habit);

        return back()->with('success', 'Habit updated successfully.');
    }

    /**
     * Toggle habit completion log for a specific date.
     */
    public function toggle(Request $request, Habit $habit): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $date = Carbon::parse($validated['date'])->format('Y-m-d');
        $minDate = Carbon::today()->subDays(30)->format('Y-m-d');

        if ($date < $minDate) {
            return back()->withErrors(['date' => 'Cannot log completions older than 30 days.']);
        }

        $log = HabitLog::where('habit_id', $habit->id)
            ->where('completed_date', $date)
            ->first();

        if ($log) {
            $log->delete();
        } else {
            HabitLog::create([
                'habit_id' => $habit->id,
                'completed_date' => $date,
            ]);
        }

        // Recalculate streak
        $this->streakService->recalculate($habit);

        return back()->with('success', 'Habit completion updated.');
    }

    /**
     * Toggle archived state of a habit.
     */
    public function toggleArchive(Habit $habit): RedirectResponse
    {
        $habit->update([
            'is_active' => !$habit->is_active,
            'archived_at' => $habit->is_active ? now() : null,
        ]);

        return back()->with('success', $habit->is_active ? 'Habit restored successfully.' : 'Habit archived successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Habit $habit): RedirectResponse
    {
        $habit->delete();

        return back()->with('success', 'Habit deleted successfully.');
    }
}
