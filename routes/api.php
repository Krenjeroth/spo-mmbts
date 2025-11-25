<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContestantController;
use App\Http\Controllers\JudgeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PageantEventController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CriterionController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\CategoryResultController;
use App\Http\Resources\UserResource;
use App\Http\Controllers\PhaseJudgeController;
use App\Http\Controllers\FinalResultController;
use App\Http\Controllers\CategoryPrintableController;
use App\Http\Controllers\JudgePrintableController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;

Route::group(['middleware' => ['auth:sanctum']], function () {
// Route::group([], function () {
  Route::get('user', function (Request $request) {
    return UserResource::make($request->user());
  });

  Route::get('users/available-for-active-phases', [UserController::class, 'availableForActivePhases']);
  
  Route::apiResource('permissions', PermissionController::class);
  Route::apiResource('roles', RoleController::class);
  Route::apiResource('contestants', ContestantController::class);
  Route::apiResource('judges', JudgeController::class);
  Route::apiResource('users', UserController::class);
  Route::apiResource('pageant-events', PageantEventController::class);
  Route::apiResource('phases', PhaseController::class);
  // Route::apiResource('scores', ScoreController::class);
  Route::apiResource('categories', CategoryController::class);
  Route::apiResource('criteria', CriterionController::class);
  Route::apiResource('category-results', CategoryResultController::class);

  // Custom Routes

  Route::get('criteria-parent', [CriterionController::class, 'getParentCriteria']);

  Route::get('permissions-all', [PermissionController::class, 'permissionAll']);

  Route::get('roles-select', [RoleController::class, 'select']);

  Route::get('categories-select', [CategoryController::class, 'select']);

  Route::get('/final-results', [FinalResultController::class, 'index']);

  // Search Routes

  Route::get('pageant-events-search', [PageantEventController::class, 'searchEvent']);

  // Route::get('phases-search', [PhaseController::class, 'searchPhase']);

  Route::get('criteria-parent-search', [CriterionController::class, 'searchParentCriteria']);

  Route::prefix('print')->group(function () {
      Route::get('category/{category}', [CategoryPrintableController::class, 'printCategory']);
      Route::get('per-judge/{category}/{judge}', [JudgePrintableController::class, 'printPerJudge']);
      Route::get('final-results', [FinalResultController::class, 'printFinalResult']);
  });

  // Route::post('scores/bulk', [ScoreController::class, 'bulkStore'])->name('scores.bulk');
  // Route::get('/scores/finalists', [ScoreController::class, 'finalists']);

  Route::apiResource('scores', ScoreController::class)->only(['index','store','update','destroy']);
  Route::post('scores/bulk', [ScoreController::class, 'bulkStore'])->name('scores.bulk');
  Route::get('scores/finalists', [ScoreController::class, 'finalists']);

  // Route::prefix('phases/{phase}')->group(function () {
  //     Route::get('judges', [PhaseJudgeController::class, 'index']);
  //     Route::post('judges', [PhaseJudgeController::class, 'store']);
  //     Route::delete('judges/{judge}', [PhaseJudgeController::class, 'destroy']);
  // });

  
});