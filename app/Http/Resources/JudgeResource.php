<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JudgeResource extends JsonResource
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
            'category_assignment' => $this->category_assignment,
            'judge_number' => $this->judge_number,
            'is_active' => $this->is_active,

            'user' => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),

            'event' => $this->whenLoaded('event', fn () => [
                'id'    => $this->event->id,
                'title' => $this->event->title ?? null,
                'year'  => $this->event->year ?? null,
            ]),

            'events' => $this->whenLoaded('events', function () {
                return $this->events->map(function ($event) {
                  return [
                      'id'    => $event->id,
                      'title'  => $event->title ?? null,
                      'year' => $event->year ?? null,
                      // add other fields you actually have on Phase
                  ];
              })->values();
            }),

            'phases' => $this->whenLoaded('phases', function () {
                return $this->phases->map(function ($phase) {
                  return [
                      'id'    => $phase->id,
                      'name'  => $phase->name ?? $phase->title ?? null,
                      'description' => $phase->description,
                      'event' => $phase->event,
                      // add other fields you actually have on Phase
                  ];
              })->values();
            }),
            
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
