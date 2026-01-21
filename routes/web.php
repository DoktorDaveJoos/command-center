<?php

use App\Http\Controllers\Web\InboxController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\SuggestionController;
use App\Http\Controllers\Webhooks\ResendInboundController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Inbox routes
    Route::get('inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('inbox/new', [InboxController::class, 'create'])->name('inbox.create');
    Route::post('inbox', [InboxController::class, 'store'])->name('inbox.store');
    Route::get('inbox/{inboxItem}', [InboxController::class, 'show'])->name('inbox.show');

    // Suggestions routes
    Route::get('suggestions', [SuggestionController::class, 'index'])->name('suggestions.web.index');

    // Workspace settings
    Route::get('workspace', [SettingsController::class, 'index'])->name('workspace.index');
});

// Webhooks (no auth required)
Route::post('webhooks/resend/inbound', [ResendInboundController::class, 'handle'])
    ->name('webhooks.resend.inbound');

require __DIR__.'/settings.php';
