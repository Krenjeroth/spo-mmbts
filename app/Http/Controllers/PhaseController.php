<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Phase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\PhaseResource;
use App\Services\CurrentEventService;
use App\Http\Requests\StorePhaseRequest;
use App\Http\Requests\UpdatePhaseRequest;

class PhaseController extends Controller
{
    public function index(Request $request) {
      $query = Phase::query()
          ->with([
              'event:id,title',
              'categories.criteria',
          ]);

      // 🔹 Determine current event
      // $eventId = $request->query('event_id') ?? CurrentEventService::getId();
      // if ($eventId) {
      //     $query->where('event_id', $eventId);
      // }

      // 🔹 Optional search
      if ($request->filled('search')) {
          $search = $request->get('search');
          $query->where('name', 'LIKE', "%{$search}%");
      }

      // 🔹 Sorting (default by order asc)
      $sort = $request->input('sort', 'order');
      $order = $request->input('order', 'asc');
      $query->orderBy($sort, $order);

      $user = User::find(Auth::id());

      // 🔹 If the user is a judge, filter to their assigned phases only
      if ($user && $user->hasRole('judge')) {
          $judge = $user->judge;

          if ($judge) {
              $query->whereHas('judges', function ($q) use ($judge) {
                  $q->where('judges.id', $judge->id);
              });
          } else {
              // If no judge profile, return empty result
              return response()->json([
                  'data' => [],
                  'meta' => [
                      'total' => 0,
                      'per_page' => 999,
                      'current_page' => 1,
                      'last_page' => 1,
                  ],
              ]);
          }
      }

      // 🔹 Check if frontend requests all (no pagination)
      $perPage = $request->integer('per_page', 10);
      if ($perPage === 999) {
          $phases = $query->get();

          return response()->json([
              'data' => PhaseResource::collection($phases),
              'meta' => [
                  'total' => $phases->count(),
                  'per_page' => $perPage,
                  'current_page' => 1,
                  'last_page' => 1,
              ],
          ]);
      }

      // 🔹 Otherwise paginate
      $phases = $query->paginate($perPage)->appends($request->query());

      return response()->json([
          'data' => PhaseResource::collection($phases),
          'meta' => [
              'total' => $phases->total(),
              'per_page' => $phases->perPage(),
              'current_page' => $phases->currentPage(),
              'last_page' => $phases->lastPage(),
          ],
      ]);
    }

    public function store(StorePhaseRequest $request) {
        $data = $request->validated();
        $phase = Phase::create($data);

        return new PhaseResource($phase);
    }

    public function show(Phase $phase) {
        return new PhaseResource($phase);
    }

    public function update(UpdatePhaseRequest $request, Phase $phase) {
        $data = $request->validated();
        $phase->update($data);

        return new PhaseResource($phase);
    }

    public function options(Request $r) {
        $eventId = CurrentEventService::getId();
        $rows = Phase::where('event_id',$eventId)
            ->where('is_active',true)
            ->orderBy('order')
            ->get(['id','name','order']);

        return $rows->map(fn($p)=>[
            'value' => $p->id,
            'label' => $p->name,
            'hint'  => 'Order '.$p->order,
        ]);
    }

    public function destroy(Phase $phase) {
        $phase->delete();
        return response()->json(['message' => 'Phase deleted successfully']);
    }
}
