<?php

namespace App\Http\Resources;

use App\Http\Resources\PageantEventResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'description' => $this->description,
            'order' => $this->order,
            'is_active' => $this->is_active,

            'event_name' => $this->whenLoaded('event', fn() => $this->event?->title),

            'categories_count' => $this->whenCounted('categories'),

            'event' => new PageantEventResource($this->whenLoaded('event')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),

            'event_phase' => $this->name . ' - ' . $this->event?->title,

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
