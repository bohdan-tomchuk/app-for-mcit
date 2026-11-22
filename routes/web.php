<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store']);
});

require __DIR__.'/settings.php';
