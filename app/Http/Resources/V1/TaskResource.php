<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->toIso8601String(),
            'project_id' => $this->project_id,
            'checklists' => $this->whenLoaded('checklists', fn () => $this->checklists->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'is_completed' => (bool) $c->is_completed,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
