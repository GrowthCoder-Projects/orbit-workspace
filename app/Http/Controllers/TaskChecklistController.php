<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskChecklistController extends Controller
{
    /**
     * Store a newly created checklist item in storage.
     */
    public function store(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'item_text' => 'required|string|max:255',
        ]);

        $task->checklists()->create([
            'item_text' => $validated['item_text'],
            'is_completed' => false,
            'sort_order' => $task->checklists()->count(),
        ]);

        return redirect()->route('tasks.index')->with('success', 'Checklist item added.');
    }

    /**
     * Update the specified checklist item in storage.
     */
    public function update(Request $request, TaskChecklist $checklist): RedirectResponse
    {
        $validated = $request->validate([
            'item_text' => 'sometimes|required|string|max:255',
            'is_completed' => 'sometimes|required|boolean',
            'sort_order' => 'sometimes|required|integer',
        ]);

        $checklist->update($validated);

        return redirect()->route('tasks.index')->with('success', 'Checklist item updated.');
    }

    /**
     * Remove the specified checklist item from storage.
     */
    public function destroy(TaskChecklist $checklist): RedirectResponse
    {
        $checklist->delete();

        return redirect()->route('tasks.index')->with('success', 'Checklist item deleted.');
    }
}
