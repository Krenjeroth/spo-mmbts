<?php

namespace App\Http\Controllers;

use App\Models\Judge;
use App\Models\Phase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class PhaseJudgeController extends Controller
{
    /**
     * Display all judges assigned to the given phase.
     */
    public function index(Phase $phase) {
        $phase->load('judges.user'); // include judge's user info

        return response()->json([
            'data' => $phase->judges,
        ]);
    }

    /**
     * Assign one or more judges to the given phase.
     */
    public function store(Request $request, Phase $phase) {
        $user = User::find(Auth::id());

        // 🔐 Only admins can assign judges
        if (!$user || !$user->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'judge_ids' => 'required|array',
            'judge_ids.*' => 'exists:judges,id',
        ]);

        $phase->judges()->syncWithoutDetaching($validated['judge_ids']);

        return response()->json([
            'message' => 'Judges assigned successfully.',
            'data' => $phase->judges()->with('user')->get(),
        ]);
    }

    /**
     * Remove a specific judge from the phase.
     */
    public function destroy(Phase $phase, Judge $judge) {
      $user = User::find(Auth::id());

        // 🔐 Only admins can unassign judges
        if (!$user || !$user->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $phase->judges()->detach($judge->id);

        return response()->json([
            'message' => 'Judge unassigned successfully.',
        ]);
    }
}
