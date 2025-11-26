<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Gate;
use App\Models\CategoryResult;
use App\Http\Resources\CategoryResultResource;
use App\Services\CurrentEventService;
use App\Models\Score;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryResultController extends Controller
{
    public function index(Request $request) {
    // Gate::authorize('result_view');

    $eventId = $request->query('event_id') ?? CurrentEventService::getId();
    $debugContestantId = $request->query('debug_contestant_id'); // optional: ?debug_contestant_id=123
    $debugCategoryId   = $request->query('debug_category_id');   // optional: ?debug_category_id=45

    // Pull only PARENT criteria rows, scoped to event
    // and ensure we only use the already-computed weighted_score
    $scores = Score::with([
            'contestant:id,name,gender',
            'criterion:id,category_id,parent_id',
            'criterion.category:id,name,weight,phase_id,event_id',
        ])
        ->where('event_id', $eventId)
        ->whereNotNull('weighted_score')
        ->whereHas('criterion', fn($q) => $q->whereNull('parent_id'))
        ->whereHas('criterion.category', fn($q) => $q->where('event_id', $eventId))
        ->get();

    if ($scores->isEmpty()) {
        return response()->json([]);
    }

    // Judges per phase, but only for phases that belong to categories of THIS event
    $phaseIds = $scores->pluck('criterion.category.phase_id')->unique()->filter();
    $judgeCountsByPhase = DB::table('judge_phase')
        ->select('phase_id', DB::raw('COUNT(judge_id) as total'))
        ->whereIn('phase_id', $phaseIds)
        ->groupBy('phase_id')
        ->pluck('total', 'phase_id');

    // Group by (category, contestant)
    $grouped = $scores->groupBy(fn($s) => $s->criterion->category->id.'-'.$s->contestant->id);

    $results = [];
    $debug   = [];

    foreach ($grouped as $key => $items) {
        $first      = $items->first();
        $category   = $first->criterion->category; // id, name, weight, phase_id
        $contestant = $first->contestant;

        // Per-judge: sum parent weighted_score (already rolled up & saved on store)
        $byJudgeParentSum = $items->groupBy('judge_id')->map(
            fn($judgeScores) => (float) $judgeScores->sum('weighted_score')
        );

        // Optional per-judge debug for a specific (category, contestant)
        if ($debugContestantId && $debugCategoryId
            && (int)$contestant->id === (int)$debugContestantId
            && (int)$category->id   === (int)$debugCategoryId) {
            $debug[] = [
                'category_id'      => $category->id,
                'contestant_id'    => $contestant->id,
                'per_judge_totals' => $byJudgeParentSum,      // e.g., 92.693, 93.746, ...
                'sum_pre_cat'      => $byJudgeParentSum->sum(),
            ];
        }

        $judgesWhoScored    = $byJudgeParentSum->count();
        $totalJudgesInPhase = (int) ($judgeCountsByPhase[$category->phase_id] ?? 0);

        // Category weight (e.g. 15% => 0.15)
        $categoryWeight = ((float) ($category->weight ?? 0)) / 100.0;

        // Average of parent weighted_score across ALL assigned judges
        $avgPreCategory = $totalJudgesInPhase > 0
            ? $byJudgeParentSum->sum() / $totalJudgesInPhase
            : 0.0;

        // Apply category weight to the averaged parent total
        $finalCategoryAverage = $avgPreCategory * $categoryWeight;

        $results[] = [
            'event_id'          => $eventId,
            'phase_id'          => $category->phase_id,
            'category_id'       => $category->id,
            'category_name'     => $category->name,
            'contestant_id'     => $contestant->id,
            'contestant_name'   => $contestant->name,
            'contestant_gender' => strtolower($contestant->gender),
            'judges_counted'    => $judgesWhoScored,
            'average_score'     => round($avgPreCategory, 5),        // e.g., 92.01070
            'category_total'    => round($finalCategoryAverage, 5),  // e.g., 13.80161 if weight=15
        ];
    }

    // Rank within (category, gender)
    $ranked = collect($results)
        ->groupBy(fn($r) => $r['category_id'].'-'.$r['contestant_gender'])
        ->flatMap(function ($group) {
            return $group->sortByDesc('category_total')
                ->values()
                ->map(function ($item, $i) {
                    $item['rank'] = $i + 1;
                    return $item;
                });
        })
        ->values();

    // Persist
    DB::transaction(function () use ($ranked) {
        foreach ($ranked as $r) {
            CategoryResult::updateOrCreate(
                [
                    'event_id'      => $r['event_id'],
                    'category_id'   => $r['category_id'],
                    'contestant_id' => $r['contestant_id'],
                ],
                [
                    'phase_id'       => $r['phase_id'],
                    'judges_counted' => $r['judges_counted'],
                    'average_score'  => $r['average_score'],
                    'category_total' => $r['category_total'],
                    'rank'           => $r['rank'],
                ]
            );
        }
    });

    // If debug is requested, include it once (non-breaking)
    if (!empty($debug)) {
        return response()->json(['ranked' => $ranked, 'debug' => $debug]);
    }

    return response()->json($ranked);
}

}
