<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Gate;
use App\Models\PageantEvent;
use App\Http\Resources\PageantEventResource;
use App\Http\Requests\StorePageantEventRequest;
use App\Http\Requests\UpdatePageantEventRequest;

class PageantEventController extends Controller
{
    public function index(Request $request) {
        // Gate::authorize('event_view');
          $query = PageantEvent::query();

        if($request->has('search')) {
          $search = $request->search;
          $query->where(function ($q) use($search) {
            $q->where('title', 'LIKE', "%{$search}%")->orWhere('year', 'LIKE', "%{$search}%");
          });
        }

        if ($request->has('status')) {
          $query->where('status', $request->status);
        }

        // Sorting (default to ID)
        if ($request->has('sort')) {
          $order = $request->input('order', 'asc');
          $query->orderBy($request->sort, $order);
        }

        // Paginate with customizable per-page count
        $pageant_events = $query->paginate($request->input('per_page', 5))->appends($request->query());

        return response()->json([
            'data' => PageantEventResource::collection($pageant_events),
            'meta' => [
                'total' => $pageant_events->total(),
                'per_page' => $pageant_events->perPage(),
                'current_page' => $pageant_events->currentPage(),
                'last_page' => $pageant_events->lastPage(),
            ]
        ]);
    }

    public function store(StorePageantEventRequest $request) {
        // Gate::authorize('event_manage');
        $event = PageantEvent::create($request->validated());
        return new PageantEventResource($event);
    }

    public function show(PageantEvent $event) {
        // Gate::authorize('event_view');
        return new PageantEventResource($event);
    }

    public function update(UpdatePageantEventRequest $request, PageantEvent $event) {
        // Gate::authorize('event_manage');
        $data = $request->validated();

        $event->update($data);
        return new PageantEventResource($event);
    }

    public function destroy(PageantEvent $event) {
        // Gate::authorize('event_manage');
        $event->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function searchEvent(Request $request) {
      $query = $request->get('q');
      $limit = (int) $request->get('limit', 20);
      $page = (int) $request->get('page', 1);
      $offset = ($page - 1) * $limit;

      $events = PageantEvent::query()
          ->when($query, fn($qBuilder) =>
              $qBuilder->where('title', 'like', "%$query%")->orWhere('year', 'like', "%$query%")
          )
          ->offset($offset)
          ->limit($limit)
          ->where('status', 'active')
          ->get();

      return response()->json([
          'data' => PageantEventResource::collection($events),
      ]);
    }
}
