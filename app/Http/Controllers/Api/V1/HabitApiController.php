<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\HabitResource;
use App\Models\Habit;
use App\Models\HabitLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HabitApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $habits = Habit::query()
            ->when(! $request->boolean('archived'), fn ($q) => $q->where('is_archived', false))
            ->latest()
            ->get();

        return $this->successResponse(HabitResource::collection($habits));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'frequency_type' => ['nullable', 'string'],
            'frequency_days' => ['nullable', 'array'],
        ]);

        $validated['frequency_type'] = $validated['frequency_type'] ?? 'daily';

        $habit = Habit::create($validated);

        return $this->successResponse(new HabitResource($habit), 'Habit created successfully', Response::HTTP_CREATED);
    }

    public function toggle(Request $request, Habit $habit): JsonResponse
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $log = HabitLog::where('habit_id', $habit->id)
            ->whereDate('completed_date', $date)
            ->first();

        if ($log) {
            $log->delete();
            $completed = false;
        } else {
            HabitLog::create([
                'habit_id' => $habit->id,
                'completed_date' => $date,
            ]);
            $completed = true;
        }

        return $this->successResponse([
            'habit_id' => $habit->id,
            'completed' => $completed,
            'date' => $date,
        ], 'Habit status toggled successfully');
    }

    public function destroy(Habit $habit): JsonResponse
    {
        $habit->delete();

        return $this->successResponse(null, 'Habit deleted successfully');
    }
}
