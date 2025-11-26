<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Judge;
use App\Models\Score;
use App\Models\Category;
use App\Models\Criterion;
use App\Models\Contestant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ScoreResource;
use App\Services\CurrentEventService;
use App\Http\Requests\StoreScoreRequest;
use App\Http\Requests\UpdateScoreRequest;
use App\Http\Requests\StoreBulkScoreRequest;

class ScoreController extends Controller
{

    public function index(Request $request) {
        $user = Auth::user();
        $currentEventId = $request->query('event_id') ?? CurrentEventService::getId();

        $query = Score::query()
            ->with(['judge.user', 'contestant', 'criterion.category.phase', 'event'])
            ->when($currentEventId, fn($q) => $q->where('event_id', $currentEventId))
            ->when($request->filled('criterion_id'), fn($q) => $q->where('criterion_id', $request->criterion_id))
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('search'), fn($q) => $q->whereHas('contestant', fn($cq) =>
                $cq->where('name', 'LIKE', '%' . $request->search . '%')
            ));

        // If user is a judge in the current event, limit to their scores AND assigned phases
        $judge = Judge::with('phases:id') // eager to avoid N+1
            ->where('user_id', $user->id)
            ->where('event_id', $currentEventId)
            ->first();

        if ($judge && !$user->hasRole('Admin')) {
            $phaseIds = $judge->phases->pluck('id');
            $query->where('judge_id', $judge->id)
                  ->whereHas('criterion.category.phase', fn($q) => $q->whereIn('id', $phaseIds));
        }

        // Whitelist sort columns to avoid SQL injection
        $sortable = ['id','score','weighted_score','updated_at','created_at'];
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : 'id';
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $order);

        $scores = $query->paginate($request->integer('per_page', 10))->appends($request->query());

        return response()->json([
            'data' => ScoreResource::collection($scores),
            'meta' => [
                'total' => $scores->total(),
                'per_page' => $scores->perPage(),
                'current_page' => $scores->currentPage(),
                'last_page' => $scores->lastPage(),
            ],
        ]);
    }

    public function store(StoreScoreRequest $request)
    {
        // Gate::authorize('score_enter');

        $data = $request->validated();

        $user = Auth::user();
        $currentEventId = $data['event_id'] ?? CurrentEventService::getId();

        // Resolve judge for this event
        $judge = Judge::where('user_id', $user->id)
            ->where('event_id', $currentEventId)
            ->where('is_active', true)
            ->first();

        if (!$judge) {
            return response()->json(['message' => 'You are not registered as a judge for this event.'], 403);
        }

        // Force server-side associations
        $data['judge_id'] = $judge->id;
        $data['event_id'] = $currentEventId;

        // Derive category + weighted
        $criterion = Criterion::with(['category','children'])->findOrFail($data['criterion_id']);
        $data['category_id'] = $criterion->category_id;

        // TEMPORARY FIX — FORCE ROUND UP TO NEXT WHOLE NUMBER (KEEP 5 DECIMALS)
        // TODO: ⚠️ REMOVE THIS IN THE FUTURE
        $rawWeighted = $data['score'] * ($criterion->percentage / 100);
        $ceiled = ceil($rawWeighted - 1e-9);
        $data['weighted_score'] = number_format($ceiled, 5, '.', '');

        $score = null;

        DB::transaction(function () use (&$score, $data, $criterion) {
            $score = Score::updateOrCreate(
                [
                    'event_id'      => $data['event_id'],
                    'judge_id'      => $data['judge_id'],
                    'contestant_id' => $data['contestant_id'],
                    'criterion_id'  => $data['criterion_id'],
                ],
                [
                    'category_id'    => $data['category_id'],
                    'score'          => $data['score'],
                    'weighted_score' => $data['weighted_score'],
                ]
            );

            if ($criterion->parent_id) {
                $this->recalculateParentWeighted(
                    $criterion->parent_id,
                    $data['contestant_id'],
                    $data['judge_id'],
                    $data['event_id']
                );
            }
        });

        $score->load(['judge.user','contestant','criterion.category','event']);

        return (new ScoreResource($score))
            ->additional(['message' => 'Score successfully recorded.'])
            ->response()
            ->setStatusCode(201);
    }



    public function update(UpdateScoreRequest $request, Score $score) {
        $user = Auth::user();

        // Ownership or Admin
        $judge = Judge::where('user_id', $user->id)
            ->where('event_id', $score->event_id)
            ->first();

        if (!$user->hasRole('Admin') && (!$judge || $score->judge_id !== $judge->id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();
        $criterion = Criterion::with(['category','children'])->findOrFail($data['criterion_id']);
        $data['category_id'] = $criterion->category_id;
        $data['weighted_score'] = round($data['score'] * ($criterion->percentage / 100), 5);

        DB::transaction(function () use ($score, $data, $criterion) {
            $score->update($data);

            if ($criterion->parent_id) {
                $this->recalculateParentWeighted(
                    $criterion->parent_id,
                    $data['contestant_id'],
                    $data['judge_id'] ?? $score->judge_id,
                    $data['event_id'] ?? $score->event_id
                );
            }
        });

        $score->load(['judge.user','contestant','criterion.category','event']);

        return (new ScoreResource($score))
            ->additional(['message' => 'Score successfully updated.'])
            ->response()
            ->setStatusCode(200);
    }
    
    public function show(Score $score) {
        $user = Auth::user();

        // Judges can only view their own score for the same event; Admin can view any
        $judge = Judge::where('user_id', $user->id)
            ->where('event_id', $score->event_id)
            ->first();

        if (!$user->hasRole('Admin') && (!$judge || $score->judge_id !== $judge->id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $score->load(['judge.user', 'contestant', 'criterion.category.phase', 'event']);

        return (new ScoreResource($score))
            ->additional(['message' => 'Score fetched.']);
    }

    public function destroy(Score $score) {
        $user = Auth::user();
        $judge = Judge::where('user_id', $user->id)
            ->where('event_id', $score->event_id)
            ->first();

        if (!$user->hasRole('Admin') && (!$judge || $score->judge_id !== $judge->id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $score->delete();
        return response()->json(['message' => 'Score successfully deleted.'], 200);
    }

    protected function recalculateParentWeighted($parentId, $contestantId, $judgeId, $eventId) {
      $parent = Criterion::with('children')->find($parentId);
      if (!$parent || !$parent->children->count()) return;

      $subtotal = 0.0;

      foreach ($parent->children as $child) {
          $childRaw = Score::where([
              'contestant_id' => $contestantId,
              'judge_id'      => $judgeId,
              'criterion_id'  => $child->id,
              'event_id'      => $eventId,
          ])->value('score');

          if ($childRaw !== null) {
              $subtotal += (float)$childRaw * ((float)$child->percentage / 100.0);
          }
      }

      $weighted = round($subtotal * ((float)$parent->percentage / 100.0), 5);

      Score::updateOrCreate(
          [
              'judge_id'      => $judgeId,
              'contestant_id' => $contestantId,
              'criterion_id'  => $parent->id,
              'event_id'      => $eventId,
          ],
          [
              'category_id'    => $parent->category_id,
              'score'          => $subtotal,    // subtotal (0..100), DO NOT clamp
              'weighted_score' => $weighted,
          ]
      );
    }

    public function bulkStore(StoreBulkScoreRequest $request) {
        $eventId = $request->input('event_id') ?? CurrentEventService::getId();
        $user = Auth::user();

        $judge = Judge::with('phases:id')
            ->where('user_id', $user->id)
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->first();

        if (!$judge) {
            return response()->json(['message' => 'You are not registered as a judge for this event.'], 403);
        }

        $incoming = collect($request->input('scores', []));

        // Load criteria once
        $criteria = Criterion::with('category','children','parent')
            ->whereIn('id', $incoming->pluck('criterion_id')->unique()->all())
            ->get()
            ->keyBy('id');

        // Optional: limit to judge’s assigned phases
        $allowedPhaseIds = $judge->phases->pluck('id')->all();

        $rows = [];
        $parentsToRecalc = collect();

        foreach ($incoming as $s) {
            $crit = $criteria->get((int)$s['criterion_id']);
            if (!$crit) continue;

            // Phase guard (if criterion belongs to a category->phase)
            if (isset($crit->category) && !empty($allowedPhaseIds)) {
                $phaseId = $crit->category->phase_id ?? null;
                if ($phaseId && !in_array($phaseId, $allowedPhaseIds, true)) {
                    // skip unauthorized criterion silently or return 403
                    continue;
                }
            }

            $raw = (float) $s['score'];
            $weighted = round($raw * ((float)$crit->percentage / 100.0), 5);

            $rows[] = [
                'event_id'       => $eventId,
                'judge_id'       => $judge->id,
                'contestant_id'  => (int)$s['contestant_id'],
                'criterion_id'   => (int)$s['criterion_id'],
                'category_id'    => (int)($crit->category_id),
                'score'          => $raw,
                'weighted_score' => $weighted,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            if ($crit->parent_id) {
                $parentsToRecalc->push([
                    'parent_id'     => $crit->parent_id,
                    'contestant_id' => (int)$s['contestant_id'],
                ]);
            }
        }

        DB::transaction(function () use ($rows, $parentsToRecalc, $judge, $eventId) {
            if ($rows) {
                Score::upsert(
                    $rows,
                    ['event_id','judge_id','contestant_id','criterion_id'],
                    ['score','weighted_score','category_id','updated_at']
                );
            }

            // Recompute unique (parent, contestant) combos
            $parentsToRecalc
                ->unique(fn($x) => $x['parent_id'].'-'.$x['contestant_id'])
                ->each(function ($item) use ($judge, $eventId) {
                    $this->recalculateParentWeighted($item['parent_id'], $item['contestant_id'], $judge->id, $eventId);
                });
        });

        return response()->json(['message' => 'Bulk scores saved successfully']);
    }

    public function finalists(Request $request) {
        $eventId = (int)($request->input('event_id') ?? CurrentEventService::getId());
        $limit   = (int)$request->input('limit', 5);
        $gender  = $request->input('gender'); // 'male' | 'female' | null

        // Prefer slugs from client; otherwise use our canonical 7
        $slugs = collect($request->input('category_slugs', []))
            ->filter()->map(fn($s) => strtolower(trim($s)))->all();

        if (empty($slugs)) {
            $slugs = [
                'ethnic-wear',
                'casual-interview',
                'advocacy-pitch',
                'swim-wear',
                'talent',
                'creative-wear',
                'formal-wear',
            ];
        }

        // Resolve category IDs by slug within this event
        $allowedCategoryIds = Category::where('event_id', $eventId)
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();

        if (!$allowedCategoryIds) {
            return response()->json(['data' => ['female' => [], 'male' => []]]);
        }

        // Sum only TOP-LEVEL criteria (parent_id is null) to avoid double-counting children.
        $base = Score::query()
            ->selectRaw('scores.contestant_id, SUM(scores.weighted_score) AS total_weighted, COUNT(DISTINCT scores.judge_id) AS judges_count')
            ->join('contestants', 'contestants.id', '=', 'scores.contestant_id')
            ->join('criteria', 'criteria.id', '=', 'scores.criterion_id')
            ->where('scores.event_id', $eventId)
            ->whereIn('criteria.category_id', $allowedCategoryIds)
            ->whereNull('criteria.parent_id')
            ->groupBy('scores.contestant_id');

        $fetchTop = function (string $g) use ($base, $limit) {
            return (clone $base)
                ->whereRaw('LOWER(contestants.gender) = ?', [strtolower($g)])
                // Rank by average across judges (fair even if a judge is missing a row)
                ->orderByRaw('CASE WHEN COUNT(DISTINCT scores.judge_id) = 0 THEN 0 ELSE SUM(scores.weighted_score) / COUNT(DISTINCT scores.judge_id) END DESC')
                // Tie-breakers
                ->orderByRaw('SUM(scores.weighted_score) DESC')
                ->orderBy('contestants.number', 'asc')
                ->orderBy('contestants.name', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($row) {
                    $c = Contestant::select('id', 'name', 'number', 'gender')->find($row->contestant_id);
                    $avg = $row->judges_count ? (float)$row->total_weighted / (int)$row->judges_count : 0.0;
                    return [
                        'contestant_id' => $c?->id,
                        'number'        => $c?->number,
                        'name'          => $c?->name,
                        'gender'        => $c?->gender,
                        'total'         => round((float)$row->total_weighted, 5),
                        'average'       => round($avg, 5),
                        'judges_count'  => (int)$row->judges_count,
                    ];
                })->values();
        };

        if ($gender) {
            $g = strtolower($gender);
            return response()->json(['data' => [$g => $fetchTop($g)]]);
        }

        return response()->json([
            'data' => [
                'female' => $fetchTop('female'),
                'male'   => $fetchTop('male'),
            ]
        ]);
    }

}
