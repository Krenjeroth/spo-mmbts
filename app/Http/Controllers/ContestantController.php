<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contestant;
use App\Http\Resources\ContestantResource;
use App\Http\Requests\StoreContestantRequest;
use App\Http\Requests\UpdateContestantRequest;

class ContestantController extends Controller
{
    public function index(Request $request) {
        $query = Contestant::query();

      // Search by name or email
      // if($request->has('search')) {
      //   $search = $request->search;
      //   $query->where(function ($q) use($search) {
      //     $q->where('username', 'LIKE', "%{$search}%")
      //     ->orWhere('email', 'LIKE', "%{$search}%")
      //     ->orWhereHas('profile', function ($q4) use($search) {
      //             $q4->where('display_name', 'like', "%$search%");
      //           });
      //   });
      // }

       // Status filter (active/inactive)
      // if ($request->has('status')) {
      //   $query->where('status', $request->status);
      // }

      // Sorting (default to ID)
      // if ($request->has('sort')) {
      //   $order = $request->input('order', 'asc');
      //   $query->orderBy($request->sort, $order);
      // }

      // Paginate with customizable per-page count
      $contestants = $query->paginate($request->input('per_page', 5))->appends($request->query());

        return response()->json([
          'data' => ContestantResource::collection($contestants),
          'meta' => [
              'total' => $contestants->total(),
              'per_page' => $contestants->perPage(),
              'current_page' => $contestants->currentPage(),
              'last_page' => $contestants->lastPage(),
          ]
      ]);
    }

    public function store(StoreContestantRequest $request) {
        $contestant = Contestant::create($request->validated());
        return new ContestantResource($contestant);
    }

    public function show(Contestant $contestant) {
        return new ContestantResource($contestant);
    }

    public function update(UpdateContestantRequest $request, Contestant $contestant) {
        $contestant->update($request->validated());
        return new ContestantResource($contestant);
    }

    public function destroy(Contestant $contestant) {
        $contestant->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
