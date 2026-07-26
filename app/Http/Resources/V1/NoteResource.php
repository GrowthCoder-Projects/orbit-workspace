<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'folder_id' => $this->folder_id,
            'folder' => $this->whenLoaded('folder', fn () => [
                'id' => $this->folder->id,
                'name' => $this->folder->name,
            ]),
            'is_favorite' => (bool) $this->is_favorite,
            'is_archived' => (bool) $this->is_archived,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
