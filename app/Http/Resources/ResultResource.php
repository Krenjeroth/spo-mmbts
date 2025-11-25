<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
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
            'contestant' => $this->contestant?->name,
            'total_score' => number_format($this->total_score, 2), // only 2 decimals for frontend
            'rank' => $this->rank,
        ];
    }
}
