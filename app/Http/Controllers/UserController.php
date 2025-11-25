<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Judge;
use App\Models\Phase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use App\Services\CurrentEventService;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    public function index(Request $request) {
      $query = User::query()->with(['judgeAssignments.event']);

      // Search by name or email
      if($request->has('search')) {
        $search = $request->search;
        $query->where(function ($q) use($search) {
          $q->where('name', 'LIKE', "%{$search}%")
          ->orWhere('email', 'LIKE', "%{$search}%");
        });
      }

       // Status filter (active/inactive)
      if ($request->has('status')) {
        $query->where('status', $request->status);
      }

      // Sorting (default to ID)
      if ($request->has('sort')) {
        $order = $request->input('order', 'asc');
        $query->orderBy($request->sort, $order);
      }

      // Paginate with customizable per-page count
      $users = $query->paginate($request->input('per_page', 5))->appends($request->query());

        return response()->json([
          'data' => UserResource::collection($users),
          'meta' => [
              'total' => $users->total(),
              'per_page' => $users->perPage(),
              'current_page' => $users->currentPage(),
              'last_page' => $users->lastPage(),
          ]
      ]);
    }

    public function store(StoreUserRequest $r) {
        return DB::transaction(function () use ($r) {
            $v = $r->validated();

            $user = User::create([
                'name'     => $v['name'],
                'email'    => $v['email'],
                'password' => $v['password'], // your model casts() already hashes
            ]);

            $roleIds = [];

            if (!empty($v['roles'])) {
                $roleIds = $this->resolveRoleIds($v['roles']);
            } elseif (!empty($v['role'])) {
                $roleIds = $this->resolveRoleIds([$v['role']]);
            }

            if (!empty($roleIds)) {
                $user->roles()->sync($roleIds);
            }

            return UserResource::make($user->load('roles.permissions'));
        });
    }

    public function update(UpdateUserRequest $r, User $user) {
        return DB::transaction(function () use ($r, $user) {
            $v = $r->validated();

            $payload = collect($v)->only(['name','email'])->toArray();
            if (array_key_exists('password', $v)) {
                $payload['password'] = $v['password']; // hashed by cast
            }
            $user->update($payload);

            $roleIds = [];

            if (!empty($v['roles'])) {
                $roleIds = $this->resolveRoleIds($v['roles']);
            } elseif (!empty($v['role'])) {
                $roleIds = $this->resolveRoleIds([$v['role']]);
            }

            if (!empty($roleIds)) {
                $user->roles()->sync($roleIds);
            }

            return UserResource::make($user->load('roles.permissions'));
        });
    }

    public function setPassword(Request $r, User $user) {
        // $this->authorize('manage_users'); // optional if using Gate/Policy
        $r->validate(['password' => 'required|string|min:8']);
        $user->update(['password' => $r->password]);
        return response()->noContent();
    }

    public function destroy(User $user) {
        // $this->authorize('manage_users');
        $user->delete();
        return response()->noContent();
    }

    public function options(Request $r) {
        $q = User::query()
            ->when($r->search, fn($qq)=>$qq->where(fn($w)=>$w
                ->where('name','like',"%{$r->search}%")
                ->orWhere('email','like',"%{$r->search}%")))
            ->orderBy('name')
            ->limit((int)$r->get('limit', 25))
            ->get(['id','name','email']);

        // standard option shape
        return $q->map(fn($u)=>[
            'value' => $u->id,
            'label' => $u->name,
            'hint'  => $u->email,
        ]);
    }

    /**
     * Return users who are NOT yet judges in the active phases (of current event).
     * Behavior:
     * - If phase_ids[] provided: exclude users who are judges assigned to ANY of those phases.
     * - Else (no phase_ids): exclude users who are judges assigned to ANY active phase of the event.
     * Optional scope param:
     * - scope=event  -> exclude all users who are judges in the event (strict; avoids unique constraint).
     * - scope=phases -> (default) only exclude users already assigned to the (active) phases.
     */
    public function availableForActivePhases(Request $r) {
        $eventId   = CurrentEventService::getId();
        $search    = (string) $r->get('search', '');
        $limit     = (int) $r->get('limit', 25);
        $scope     = $r->get('scope', 'phases'); // 'phases' | 'event'
        $phaseIds  = (array) $r->get('phase_ids', []);

        // NEW: role-based filters
        $excludeRoles = array_filter((array) $r->get('exclude_roles', [])); // e.g. ['administrator','tabulator']
        $requireRole  = $r->get('require_role'); // e.g. 'judge'
        $excludeSelf  = $r->boolean('exclude_self', false);

        // Resolve relevant phases (active by default)
        if (empty($phaseIds)) {
            $phaseIds = Phase::where('event_id', $eventId)
                ->where('is_active', true)
                ->pluck('id')->all();
        } else {
            $phaseIds = Phase::where('event_id', $eventId)
                ->whereIn('id', $phaseIds)
                ->pluck('id')->all();
        }

        // Determine users to exclude based on scope
        if ($scope === 'event') {
            $excludeUserIds = Judge::where('event_id', $eventId)->pluck('user_id')->all();
        } else {
            if (empty($phaseIds)) {
                $excludeUserIds = [];
            } else {
                $judgeIdsInPhases = DB::table('judge_phase')->whereIn('phase_id', $phaseIds)->pluck('judge_id');
                $excludeUserIds   = Judge::where('event_id', $eventId)
                                        ->whereIn('id', $judgeIdsInPhases)
                                        ->pluck('user_id')->all();
            }
        }

        $q = User::query()
            ->when(!empty($excludeUserIds), fn($qq)=>$qq->whereNotIn('id', $excludeUserIds))
            ->when($search, fn($qq)=>$qq->where(fn($w)=>$w
                ->where('name','like',"%{$search}%")
                ->orWhere('email','like',"%{$search}%")))
            // NEW: require a specific role (e.g., only users with role 'judge')
            ->when($requireRole, fn($qq)=>$qq->whereHas('roles', fn($rq)=>$rq->where('title', $requireRole)))
            // NEW: exclude users with any of these roles (e.g., admin, tabulator)
            ->when(!empty($excludeRoles), fn($qq)=>$qq->whereDoesntHave('roles', fn($rq)=>$rq->whereIn('title', $excludeRoles)))
            // NEW: optionally exclude the current user
            ->when($excludeSelf && $r->user(), fn($qq)=>$qq->where('id', '!=', $r->user()->id))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id','name','email']);

        return $q->map(fn($u)=>[
            'value' => $u->id,
            'label' => $u->name,
            'hint'  => $u->email,
        ]);
    }

    private function resolveRoleIds(array $roles): array {
        // Accept IDs or titles
        $ids = [];
        foreach ($roles as $r) {
            if (ctype_digit((string)$r)) {
                $ids[] = (int)$r;
            } else {
                $role = Role::firstWhere('title', $r);
                if ($role) $ids[] = $role->id;
            }
        }
        return array_values(array_unique($ids));
    }
}
