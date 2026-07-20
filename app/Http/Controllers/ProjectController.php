<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects.
     */
    public function index(): Response
    {
        $projects = Project::withCount(['tasks', 'tasks as done_tasks_count' => function ($query) {
            $query->where('status', 'done');
        }])
            ->with(['milestones' => function ($query) {
                $query->where('status', 'pending')->orderBy('due_date')->limit(1);
            }])
            ->latest()
            ->get()
            ->map(function (Project $project) {
                $total = $project->tasks_count;
                $done = $project->done_tasks_count;
                $project->progress_percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;

                return $project;
            });

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): Response
    {
        return Inertia::render('Projects/Create');
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated());

        return redirect()->route('projects.show', $project)->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): Response
    {
        $project->load(['milestones', 'tasks.checklists', 'tasks.projectMilestone']);

        $taskStats = [
            'total' => $project->tasks()->count(),
            'done' => $project->tasks()->where('status', 'done')->count(),
            'in_progress' => $project->tasks()->where('status', 'in_progress')->count(),
            'overdue' => $project->tasks()->where('status', '!=', 'done')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count(),
        ];

        $total = $taskStats['total'];
        $progressPercent = $total > 0 ? (int) round(($taskStats['done'] / $total) * 100) : 0;

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'taskStats' => $taskStats,
            'progressPercent' => $progressPercent,
        ]);
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project): Response
    {
        return Inertia::render('Projects/Edit', [
            'project' => $project,
        ]);
    }

    /**
     * Update the specified project in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('projects.show', $project)->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
