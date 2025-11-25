<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event_name = $this->whenLoaded('event', fn() => $this->event?->title);
        $phase_name = $this->whenLoaded('phase', fn() => $this->phase?->name);

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'phase_id' => $this->phase_id, // ✅ Added phase reference

            'name' => $this->name,
            'slug' => $this->slug,
            'weight' => $this->weight,
            'order' => $this->order,
            'is_active' => $this->is_active,

            // 'phase' => $this->whenLoaded('phase', fn() => $this->phase?->name),

            // ✅ Lightweight related data
            // 'event_name' => $this->whenLoaded('event', fn() => $this->event?->title),
            // 'phase_name' => $this->whenLoaded('phase', fn() => $this->phase?->name),

            'event_title' => $this->event?->title,
            'event_category' => $this->name . ' - ' . $this->event?->title,

            // ✅ Criteria list (already defined)
            'criteria' => CriterionResource::collection($this->whenLoaded('criteria')),

            // ✅ Timestamps
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
