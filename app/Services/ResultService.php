<?php

namespace App\Services;

use App\Models\Score;
use App\Models\Result;
use App\Models\Category;

class ResultService
{
    public static function calculateForEvent(int $eventId): void {
        $categories = Category::where('event_id', $eventId)->with('criteria')->get();
        $contestantScores = [];

        // Step 1: Loop all scores per contestant
        $scores = Score::where('event_id', $eventId)
            ->with(['criterion', 'category'])
            ->get();

        foreach ($scores as $score) {
            $cid = $score->contestant_id;

            if (!isset($contestantScores[$cid])) {
                $contestantScores[$cid] = 0;
            }

            $criterion = $score->criterion;
            $category = $score->category;

            $weight = ($criterion->percentage / 100) * ($category->weight / 100);
            $contestantScores[$cid] += $score->score * $weight;
        }

        // Step 2: Save results
        foreach ($contestantScores as $contestantId => $total) {
            Result::updateOrCreate(
                ['event_id' => $eventId, 'contestant_id' => $contestantId],
                ['total_score' => round($total, 3)] // keep 3 decimals in DB
            );
        }

        // Step 3: Rank contestants
        $ranked = Result::where('event_id', $eventId)
            ->orderByDesc('total_score')
            ->get();

        $rank = 1;
        foreach ($ranked as $result) {
            $result->update(['rank' => $rank++]);
        }
    }
}
