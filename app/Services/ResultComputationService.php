<?php

namespace App\Services;

use App\Models\CategoryResult;
use App\Models\PageantEvent;
use App\Models\Result;
use Illuminate\Support\Facades\DB;

class ResultComputationService
{
    /**
     * Compute and store category-level results for an event.
     */
    public function computeCategoryResults(int $eventId): void
    {
        $event = PageantEvent::with(['categories.criteria', 'contestants.scores.criterion', 'contestants.scores.judge'])
            ->findOrFail($eventId);

        DB::transaction(function () use ($event) {
            foreach ($event->categories as $category) {
                $categoryPercent = $category->percent / 100;

                foreach ($event->contestants as $contestant) {
                    // Group by judge and sum weighted scores
                    $judgeTotals = $contestant->scores
                        ->whereIn('criterion_id', $category->criteria->pluck('id'))
                        ->groupBy('judge_id')
                        ->map(fn($scores) => $scores->sum('weighted_score'));

                    $average = round($judgeTotals->avg() ?? 0, 5);
                    $categoryTotal = round($average * $categoryPercent, 5);

                    CategoryResult::updateOrCreate([
                        'contestant_id' => $contestant->id,
                        'category_id' => $category->id,
                        'event_id' => $event->id,
                    ], [
                        'average_score' => $average,
                        'category_total' => $categoryTotal,
                    ]);
                }
            }
        });
    }

    /**
     * Compute and store final results per contestant (total + rank).
     */
    public function computeFinalResults(int $eventId): void
    {
        $event = PageantEvent::with(['contestants.categoryResults'])->findOrFail($eventId);

        DB::transaction(function () use ($event) {
            foreach ($event->contestants as $contestant) {
                $totalScore = round($contestant->categoryResults->sum('category_total'), 5);

                Result::updateOrCreate([
                    'contestant_id' => $contestant->id,
                    'event_id' => $event->id,
                ], [
                    'total_score' => $totalScore,
                ]);
            }

            // Assign ranks
            $results = Result::where('event_id', $event->id)
                ->orderByDesc('total_score')
                ->get();

            foreach ($results as $i => $result) {
                $result->update(['rank' => $i + 1]);
            }
        });
    }

    /**
     * Combined computation (category + final results).
     */
    public function computeAll(int $eventId): void
    {
        $this->computeCategoryResults($eventId);
        $this->computeFinalResults($eventId);
    }
}
