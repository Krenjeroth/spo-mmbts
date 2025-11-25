<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContestantResource extends JsonResource
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
            'name' => $this->name,
            'gender' => $this->gender,
            'municipality_id' => $this->municipality_id,
            'municipality' => $this->whenLoaded('municipality', function () {
                return $this->municipality?->name;
            }),
            'event_id' => $this->event_id,
            'event' => $this->whenLoaded('event', function () {
                return $this->event?->title . ' ' . $this->event?->year;
            }),
            'number' => $this->number,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
