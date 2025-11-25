<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CriterionResource extends JsonResource
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
            'category_id' => $this->category_id ?? $this->parent?->category_id,
            'event_id' => $this->event_id,
            'name' => $this->name,
            'search_name' => $this->name . ' ('. $this->category->name . ') - ' . $this->percentage . '%',
            'percentage' => $this->percentage,
            'order' => $this->order,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'parent' => new CriterionResource($this->whenLoaded('parent')),
            'children' => CriterionResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
