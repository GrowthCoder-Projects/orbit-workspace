<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\CalendarEventResource;
use App\Models\CalendarEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CalendarEventApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = CalendarEvent::query();

        if ($start = $request->query('start_date')) {
            $query->where('start_at', '>=', $start);
        }

        if ($end = $request->query('end_date')) {
            $query->where('end_at', '<=', $end);
        }

        $events = $query->orderBy('start_at')->get();

        return $this->successResponse(CalendarEventResource::collection($events));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'is_all_day' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $event = CalendarEvent::create($validated);

        return $this->successResponse(new CalendarEventResource($event), 'Calendar event created successfully', Response::HTTP_CREATED);
    }

    public function destroy(CalendarEvent $event): JsonResponse
    {
        $event->delete();

        return $this->successResponse(null, 'Calendar event deleted successfully');
    }
}
