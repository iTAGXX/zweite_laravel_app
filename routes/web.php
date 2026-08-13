<?php

use App\Enums\PermissionName;
use App\Http\Controllers\WebAppManifestController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::view('/', 'welcome')->name('home');

Route::get('/manifest.webmanifest', WebAppManifestController::class)->name('pwa.manifest');
Route::view('/offline', 'pwa.offline')->name('pwa.offline');
Route::get('/sw.js', function (): BinaryFileResponse {
    return response()->file(public_path('sw.js'), [
        'Content-Type' => 'application/javascript',
        'Cache-Control' => 'no-cache',
        'Service-Worker-Allowed' => '/',
    ]);
})->name('pwa.service-worker');

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('finance', 'finance')
        ->middleware('can:'.PermissionName::FinanceView->value)
        ->name('finance');
    Route::view('members', 'members')
        ->middleware('can:'.PermissionName::UsersManage->value)
        ->name('members');

    if (app()->environment(['local', 'testing'])) {
        Route::view('dev/ui', 'dev.ui')->name('dev.ui');
    }
});

require __DIR__.'/settings.php';
