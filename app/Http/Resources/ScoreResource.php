<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'event_id'       => $this->event_id,
            'category_id'    => $this->category_id,
            'criterion_id'   => $this->criterion_id,
            'judge_id'       => $this->judge_id,
            'contestant_id'  => $this->contestant_id,

            'score'          => (float) $this->score,
            'weighted_score' => (float) $this->weighted_score,

            'contestant' => $this->whenLoaded('contestant', function () {
                return [
                    'id'   => $this->contestant->id,
                    'name' => $this->contestant->name,
                    'municipality' => $this->contestant->municipality ?? null,
                    'gender' => $this->contestant->gender ?? null,
                ];
            }),

            'judge' => new JudgeResource($this->whenLoaded('judge')),


            'criterion' => $this->whenLoaded('criterion', function () {
                return [
                    'id'         => $this->criterion->id,
                    'name'       => $this->criterion->name,
                    'percentage' => (float) $this->criterion->percentage,
                    'category'   => $this->whenLoaded('criterion.category', function () {
                        return [
                            'id'     => $this->criterion->category->id,
                            'name'   => $this->criterion->category->name,
                            'weight' => (float) $this->criterion->category->weight,
                        ];
                    }),
                ];
            }),

            'event' => $this->whenLoaded('event', function () {
                return [
                    'id'   => $this->event->id,
                    'name' => $this->event->name,
                    'year' => $this->event->year,
                ];
            }),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
