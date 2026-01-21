<?php

use App\Http\Controllers\InboxItemController;
use App\Http\Controllers\SuggestionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('inbox-items', InboxItemController::class)->except(['destroy']);
    Route::post('inbox-items/{inboxItem}/extract', [InboxItemController::class, 'extract'])->name('inbox-items.extract');

    Route::get('suggestions', [SuggestionController::class, 'index'])->name('suggestions.index');
    Route::get('suggestions/{suggestion}', [SuggestionController::class, 'show'])->name('suggestions.show');
    Route::post('suggestions/{suggestion}/accept', [SuggestionController::class, 'accept'])->name('suggestions.accept');
    Route::post('suggestions/{suggestion}/reject', [SuggestionController::class, 'reject'])->name('suggestions.reject');
});
