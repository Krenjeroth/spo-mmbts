<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Criterion;
use Illuminate\Http\Request;
use App\Http\Resources\CriterionResource;
use App\Http\Requests\StoreCriterionRequest;
use App\Http\Requests\UpdateCriterionRequest;

class CriterionController extends Controller
{
  public function index(Request $request) {
      $query = Criterion::query()->with('category', 'parent', 'children', 'children.children');

      // 🔹 Category filter
      if ($request->has('category_id')) {
          $query->where('category_id', $request->category_id);
      }

      // 🔹 Search filter
      if ($request->has('search')) {
          $search = $request->search;
          $query->where('name', 'LIKE', "%{$search}%");
      }

      // 🔹 Sorting
      $query->orderBy($request->input('sort', 'id'), $request->input('order', 'asc'));

      $criteria = $query->paginate(
          $request->input('per_page', 10)
      )->appends($request->query());

      return response()->json([
          'data' => CriterionResource::collection($criteria),
          'meta' => [
              'total' => $criteria->total(),
              'per_page' => $criteria->perPage(),
              'current_page' => $criteria->currentPage(),
              'last_page' => $criteria->lastPage(),
          ]
      ]);
  }

  public function store(StoreCriterionRequest $request) {
        $data = $request->validated();
        $event_id = Category::where('id', $data['category_id'])->value('event_id');
        $data['event_id'] = $event_id;
        $criterion = Criterion::create($data);

        // return new CriterionResource($criterion->load('category'));
        return new CriterionResource($criterion);
    }

    public function update(UpdateCriterionRequest $request, Criterion $criterion) {
        $data = $request->validated();
        $event_id = Category::where('id', $data['category_id'])->value('event_id');
        $data['event_id'] = $event_id;
        $criterion->update($data);

        return new CriterionResource($criterion->load('category'));
    }

    public function destroy(Criterion $criterion) {
        $criterion->delete();

        return response()->json(['message' => 'Criterion deleted successfully.']);
    }

    public function getParentCriteria(Request $request) {
      $query = $request->get('q');
      $limit = (int) $request->get('limit', 20);
      $page = (int) $request->get('page', 1);
      $offset = ($page - 1) * $limit;

      $criteria = Criterion::query()
          ->when($query, fn($qBuilder) =>
              $qBuilder->where('name', 'like', "%$query%")
          )
          ->offset($offset)
          ->limit($limit)
          ->whereNull('parent_id')
          ->get();

      return response()->json([
          'data' => CriterionResource::collection($criteria),
      ]);
    }

    public function searchParentCriteria(Request $request) {
      $query = $request->get('q');
      $limit = (int) $request->get('limit', 20);
      $page = (int) $request->get('page', 1);
      $offset = ($page - 1) * $limit;

      $criteria = Criterion::query()
          ->when($query, fn($qBuilder) =>
              $qBuilder->where('name', 'like', "%$query%")
          )
          ->offset($offset)
          ->limit($limit)
          ->whereNull('parent_id')
          ->get();

      return response()->json([
          'data' => CriterionResource::collection($criteria),
      ]);
    }
}
