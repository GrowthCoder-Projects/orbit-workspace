<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskTimeLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskTimeLogController extends Controller
{
    /**
     * Store a newly created time log in storage.
     */
    public function store(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'duration_seconds' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $task->timeLogs()->create([
            'started_at' => $validated['started_at'],
            'ended_at' => $validated['ended_at'] ?? now(),
            'duration_seconds' => $validated['duration_seconds'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Waktu fokus berhasil dicatat.');
    }

    /**
     * Remove the specified time log from storage.
     */
    public function destroy(TaskTimeLog $timeLog): RedirectResponse
    {
        $timeLog->delete();

        return redirect()->back()->with('success', 'Log waktu berhasil dihapus.');
    }
}
