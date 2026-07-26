<?php

use App\Http\Controllers\Api\V1\BookmarkApiController;
use App\Http\Controllers\Api\V1\CalendarEventApiController;
use App\Http\Controllers\Api\V1\FinanceTransactionApiController;
use App\Http\Controllers\Api\V1\HabitApiController;
use App\Http\Controllers\Api\V1\NoteApiController;
use App\Http\Controllers\Api\V1\TaskApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->name('api.v1.')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    })->name('user');

    Route::prefix('v1')->group(function () {
        // Notes API
        Route::apiResource('notes', NoteApiController::class);

        // Tasks API
        Route::apiResource('tasks', TaskApiController::class);

        // Habits API
        Route::apiResource('habits', HabitApiController::class)->except(['create', 'edit', 'show', 'update']);
        Route::post('habits/{habit}/toggle', [HabitApiController::class, 'toggle'])->name('habits.toggle');

        // Finance Transactions API
        Route::apiResource('finance/transactions', FinanceTransactionApiController::class)->except(['create', 'edit', 'show', 'update'])->names('finance.transactions');

        // Bookmarks API
        Route::apiResource('bookmarks', BookmarkApiController::class)->except(['create', 'edit', 'show', 'update']);

        // Calendar API
        Route::apiResource('calendar/events', CalendarEventApiController::class)->except(['create', 'edit', 'show', 'update'])->names('calendar.events');
    });
});
