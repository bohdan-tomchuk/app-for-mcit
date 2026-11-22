<?php

use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketStatusController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('tickets/{ticket}/status', TicketStatusController::class)->name('tickets.status');
});

require __DIR__.'/settings.php';
