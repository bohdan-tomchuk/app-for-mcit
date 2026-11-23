<?php

use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketStatusController;
use Illuminate\Support\Facades\Route;

// Digits only, at most 18: longer ids overflow bigint and must be a plain 404.
Route::pattern('ticket', '[0-9]{1,18}');

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::patch('tickets/{ticket}/status', TicketStatusController::class)->name('tickets.status');
});

require __DIR__.'/settings.php';
