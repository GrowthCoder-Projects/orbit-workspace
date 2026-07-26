<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\NoteResource;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoteApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Note::query()->with('folder');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($folderId = $request->query('folder_id')) {
            $query->where('folder_id', $folderId);
        }

        if ($request->boolean('favorites')) {
            $query->where('is_favorite', true);
        }

        $notes = $query->latest()->paginate($request->query('per_page', 15));

        return $this->successResponse(NoteResource::collection($notes)->response()->getData(true));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'exists:folders,id'],
            'is_favorite' => ['nullable', 'boolean'],
            'is_archived' => ['nullable', 'boolean'],
        ]);

        $note = Note::create($validated);

        return $this->successResponse(new NoteResource($note), 'Note created successfully', Response::HTTP_CREATED);
    }

    public function show(Note $note): JsonResponse
    {
        $note->load(['folder', 'backlinks', 'outgoingLinks']);

        return $this->successResponse(new NoteResource($note));
    }

    public function update(Request $request, Note $note): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'folder_id' => ['nullable', 'exists:folders,id'],
            'is_favorite' => ['nullable', 'boolean'],
            'is_archived' => ['nullable', 'boolean'],
        ]);

        $note->update($validated);

        return $this->successResponse(new NoteResource($note), 'Note updated successfully');
    }

    public function destroy(Note $note): JsonResponse
    {
        $note->delete();

        return $this->successResponse(null, 'Note deleted successfully');
    }
}
