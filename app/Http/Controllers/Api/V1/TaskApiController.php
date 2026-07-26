<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Task::query()->with('checklists');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($projectId = $request->query('project_id')) {
            $query->where('project_id', $projectId);
        }

        $tasks = $query->latest()->paginate($request->query('per_page', 15));

        return $this->successResponse(TaskResource::collection($tasks)->response()->getData(true));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $task = Task::create($validated);

        return $this->successResponse(new TaskResource($task), 'Task created successfully', Response::HTTP_CREATED);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load('checklists');

        return $this->successResponse(new TaskResource($task));
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $task->update($validated);

        return $this->successResponse(new TaskResource($task), 'Task updated successfully');
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return $this->successResponse(null, 'Task deleted successfully');
    }
}
