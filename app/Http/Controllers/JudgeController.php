<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Judge;
use App\Models\Phase;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\JudgeResource;
use App\Services\CurrentEventService;
use App\Http\Requests\StoreJudgeRequest;
use App\Http\Requests\UpdateJudgeRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\AssignJudgePhasesRequest;

class JudgeController extends Controller
{

    public function index(Request $request) {
      $query = Judge::query()->with(['user', 'event', 'phases']);

      $eventId = $request->query('event_id') ?? CurrentEventService::getId();
      if ($eventId) {
          $query->where('event_id', $eventId);
      }

      $phaseId = $request->query('phase_id');
      $categoryId = $request->query('category_id');

      if ($categoryId && !$phaseId) {
          // Resolve phase from category, if only category_id was provided
          $cat = Category::select('id','phase_id')->find($categoryId);
          if ($cat) $phaseId = $cat->phase_id;
      }

      if ($phaseId) {
          $query->whereHas('phases', function ($q) use ($phaseId) {
              $q->where('phases.id', $phaseId);
          });
      }

      if ($request->has('is_active')) {
          $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
      }

      if ($request->has('sort')) {
          $order = $request->input('order', 'asc');
          $query->orderBy($request->sort, $order);
      } else {
          $query->orderByRaw('CASE WHEN judge_number IS NULL THEN 1 ELSE 0 END, judge_number ASC, id ASC');
      }

      if ($request->boolean('all')) {
          $collection = $query->get();
          return response()->json([
              'data' => JudgeResource::collection($collection),
              'meta' => [
                  'total' => $collection->count(),
                  'per_page' => $collection->count(),
                  'current_page' => 1,
                  'last_page' => 1,
              ],
          ]);
      }

      $perPage = (int) $request->input('per_page', 10);
      $judges = $query->paginate($perPage)->appends($request->query());

      return response()->json([
          'data' => JudgeResource::collection($judges),
          'meta' => [
              'total'        => $judges->total(),
              'per_page'     => $judges->perPage(),
              'current_page' => $judges->currentPage(),
              'last_page'    => $judges->lastPage(),
          ]
      ]);
    }

    public function store(StoreJudgeRequest $r) {
        return DB::transaction(function () use ($r) {
            $v = $r->validated();
            $eventId = CurrentEventService::getId();

            // 1) Try to use existing user first
            $userId = $v['user_id'] ?? null;

            // 2) If no existing user, but "create_user" is checked, create one
            if (empty($userId) && !empty($v['create_user'])) {
                $user = User::create([
                    'name'     => $v['user_name'],
                    'email'    => $v['user_email'],
                    'password' => $v['user_password'], // model casts hash
                ]);

                // Ensure judge role exists (optional)
                $judgeRole = Role::firstOrCreate(['title' => 'judge']);
                $user->roles()->syncWithoutDetaching([$judgeRole->id]);

                $userId = $user->id;
            }

            // 3) If still no user, throw a validation error
            if (empty($userId)) {
                throw ValidationException::withMessages([
                    'user_id' => ['Please link an existing user or create a new one.'],
                ]);
            }

            // 4) Create judge (unique per user+event)
            $judge = Judge::create([
                'user_id'             => $userId,
                'event_id'            => $eventId,
                'category_assignment' => $v['category_assignment'] ?? null,
                'judge_number'        => $v['judge_number'] ?? null,
                'is_active'           => $v['is_active'] ?? true,
            ]);

            // 5) Optional immediate phase assignment
            if (!empty($v['phase_ids'])) {
                $this->syncJudgePhases($judge, $v['phase_ids'], $eventId);
            }

            return new JudgeResource(
                $judge->load(['user', 'event', 'phases'])
            );
        });
    }

    public function update(UpdateJudgeRequest $r, Judge $judge) {
        // // Guard: ensure same-event judge (safety if route model binding isn’t scoped)
        // abort_if($judge->event_id !== CurrentEventService::getId(), 403);

        // $judge->update($r->validated());

        // return new JudgeResource($judge->load(['user','event','phases']));
        $data = $r->validate([
            'category_assignment' => ['nullable','string','max:120'],
            'judge_number'        => ['nullable','integer','min:1'],
            'is_active'           => ['sometimes','boolean'],
            'phase_ids'           => ['array'],
            'phase_ids.*'         => ['integer','exists:phases,id'],
        ]);

        $judge->fill($data);
        $judge->save();

        if ($r->has('phase_ids')) {
            $validPhaseIds = Phase::where('event_id', $judge->event_id)
                ->whereIn('id', $r->input('phase_ids', []))
                ->pluck('id')->all();
            $judge->phases()->sync($validPhaseIds);
        }

        return new JudgeResource(
            $judge->load(['user','event','phases'])
        );
    }

    public function destroy(Judge $judge)
    {
        abort_if($judge->event_id !== CurrentEventService::getId(), 403);
        $judge->delete();
        return response()->noContent();
    }

    public function assignPhases(AssignJudgePhasesRequest $r, Judge $judge) {
        abort_if($judge->event_id !== CurrentEventService::getId(), 403);

        $eventId = $judge->event_id;
        $validPhaseIds = Phase::forEvent($eventId)
                   ->whereIn('id', $r->phase_ids ?? [])
                   ->pluck('id')->all();
        $judge->phases()->sync($validPhaseIds);

        return new JudgeResource($judge->load(['user','event','phases']));
    }

    public function options(Request $r) {
        $eventId = CurrentEventService::getId();

        $q = Judge::query()
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->with('user:id,name,email')
            ->when($r->search, fn($qq)=>$qq->where(fn($w)=>$w
                ->where('category_assignment','like',"%{$r->search}%")
                ->orWhereHas('user', fn($uq)=>$uq
                    ->where('name','like',"%{$r->search}%")
                    ->orWhere('email','like',"%{$r->search}%"))))
            ->orderByRaw('COALESCE(judge_number, 9999)')
            ->limit((int)$r->get('limit', 50))
            ->get(['id','user_id','judge_number','category_assignment']);

        return $q->map(fn($j)=>[
            'value' => $j->id,
            'label' => $j->user?->name ?? "Judge #{$j->id}",
            'hint'  => trim(collect([
                          $j->user?->email,
                          $j->category_assignment,
                          $j->judge_number ? 'No. '.$j->judge_number : null
                      ])->filter()->join(' • ')),
        ]);
    }

    public function availableUsers(Request $r) {
        $eventId = CurrentEventService::getId();
        $already = Judge::where('event_id', $eventId)->pluck('user_id');

        $q = User::query()
            ->whereNotIn('id', $already)
            ->when($r->search, fn($qq)=>$qq->where(fn($w)=>$w
                ->where('name','like',"%{$r->search}%")
                ->orWhere('email','like',"%{$r->search}%")))
            ->orderBy('name')
            ->limit((int)$r->get('limit', 25))
            ->get(['id','name','email']);

        return $q->map(fn($u)=>[
            'value' => $u->id,
            'label' => $u->name,
            'hint'  => $u->email,
        ]);
    }

    private function syncJudgePhases(Judge $judge, array $phaseIds, int $eventId): void {
        // Only allow phases from the same event
        $valid = Phase::where('event_id', $eventId)->whereIn('id', $phaseIds)->pluck('id')->all();
        $judge->phases()->sync($valid);
    }
}
