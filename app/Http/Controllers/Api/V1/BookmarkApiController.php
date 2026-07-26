<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\BookmarkResource;
use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookmarkApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Bookmark::query();

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->boolean('favorite')) {
            $query->where('is_favorite', true);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $bookmarks = $query->latest()->paginate($request->query('per_page', 15));

        return $this->successResponse(BookmarkResource::collection($bookmarks)->response()->getData(true));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:bookmark_categories,id'],
            'is_favorite' => ['nullable', 'boolean'],
        ]);

        $bookmark = Bookmark::create($validated);

        return $this->successResponse(new BookmarkResource($bookmark), 'Bookmark created successfully', Response::HTTP_CREATED);
    }

    public function destroy(Bookmark $bookmark): JsonResponse
    {
        $bookmark->delete();

        return $this->successResponse(null, 'Bookmark deleted successfully');
    }
}
