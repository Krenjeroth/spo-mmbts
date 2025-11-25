<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ContestantResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PageantEventResource;

class CategoryResultResource extends JsonResource
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
        'contestant' => new ContestantResource($this->whenLoaded('contestant')),
        'category' => new CategoryResource($this->whenLoaded('category')),
        'event' => new PageantEventResource($this->whenLoaded('event')),
        'average_score' => number_format($this->average_score, 5, '.', ''),
        'category_total' => number_format($this->category_total, 5, '.', ''),
        'created_at' => $this->created_at?->toDateTimeString(),
        'updated_at' => $this->updated_at?->toDateTimeString(),
    ];
    }
}
