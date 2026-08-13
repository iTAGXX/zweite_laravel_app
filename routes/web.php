<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    if (app()->environment(['local', 'testing'])) {
        Route::view('dev/ui', 'dev.ui')->name('dev.ui');
    }
});

require __DIR__.'/settings.php';
