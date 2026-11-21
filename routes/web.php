<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::inertia('tickets', 'tickets/Index')->name('tickets.index');
});

require __DIR__.'/settings.php';
