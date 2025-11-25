<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Phase;
use App\Http\Resources\CategoryResource;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;
use App\Services\CurrentEventService;

class CategoryController extends Controller
{
    public function index(Request $request) {
        $query = Category::query()->with(['criteria', 'event', 'phase', 'criteria.children']);

        // 🔹 Event filter
        $eventId = $request->query('event_id') 
            ?? CurrentEventService::getId();
        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        // 🔹 Phase filter (new!)
        if ($phaseId = $request->query('phase_id')) {
            $query->where('phase_id', $phaseId);
        }

        // 🔹 Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        // 🔹 Sorting
        $query->orderBy($request->input('sort', 'id'), $request->input('order', 'asc'));

        $categories = $query->paginate(
            $request->input('per_page', 10)
        )->appends($request->query());

        return response()->json([
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
            ]
        ]);
    }

    public function store(StoreCategoryRequest $request) {
      // Gate::authorize('agency_store');
      
      $data = $request->validated();
      // $event_id = CurrentEventService::getId();
      $event_id = Phase::where('id', $data['phase_id'])->value('event_id');
      $data['event_id'] = $event_id;

      $category = Category::create($data);

      return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category) {
      // Gate::authorize('agency_update');

      $data = $request->validated();
      $event_id = Phase::where('id', $data['phase_id'])->value('event_id');
      $data['event_id'] = $event_id;

      $category->update($data);

      return new CategoryResource($category);
    }

    public function destroy(Category $category) {
      // Gate::authorize('agency_destroy');

      $category->delete();
      
      return new CategoryResource($category);
    }

    public function select() {
        // ?? Member no active membership
        $categories = Category::all();
        // $members = Member::with('memberships')->whereDoesntHave('memberships', function ($query) {
        //     $query->where('status', true);
        // })->get();

        return response()->json([
          'data' => CategoryResource::collection($categories)
        ]);
    }
}
