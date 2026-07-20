<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectMilestoneController extends Controller
{
    /**
     * Store a newly created milestone in storage.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['project_id'] = $project->id;
        $validated['status'] = 'pending';
        $validated['sort_order'] = $validated['sort_order'] ?? $project->milestones()->max('sort_order') + 1;

        $project->milestones()->create($validated);

        return redirect()->route('projects.show', $project)->with('success', 'Milestone added successfully.');
    }

    /**
     * Update the specified milestone in storage.
     */
    public function update(Request $request, ProjectMilestone $milestone): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', 'in:pending,completed'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $milestone->update($validated);

        return redirect()->route('projects.show', $milestone->project_id)->with('success', 'Milestone updated successfully.');
    }

    /**
     * Remove the specified milestone from storage.
     */
    public function destroy(ProjectMilestone $milestone): RedirectResponse
    {
        $projectId = $milestone->project_id;
        $milestone->delete();

        return redirect()->route('projects.show', $projectId)->with('success', 'Milestone deleted successfully.');
    }
}
