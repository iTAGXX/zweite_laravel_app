<?php

use App\Enums\ModuleName;
use App\Enums\PermissionName;
use App\Http\Controllers\AcceptInvitationController;
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

Route::middleware('auth')->get('invitations/{invitation}/{token}', AcceptInvitationController::class)
    ->name('invitations.accept');

Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::middleware('can:'.PermissionName::PeopleManage->value)->group(function () {
        Route::livewire('people', 'pages::people.index')->name('people.index');
        Route::livewire('people/create', 'pages::people.create')->name('people.create');
        Route::livewire('people/{person}/edit', 'pages::people.edit')->name('people.edit');
    });
    Route::view('finance', 'finance')
        ->middleware('can:'.PermissionName::FinanceView->value)
        ->name('finance');
    Route::view('members', 'members')
        ->middleware(['can:'.PermissionName::UsersManage->value, 'module:'.ModuleName::Club->value])
        ->name('members');
    Route::view('stable', 'stable')
        ->middleware('module:'.ModuleName::Stable->value)
        ->name('stable');
    Route::view('audit', 'audit')
        ->middleware('can:'.PermissionName::AuditView->value)
        ->name('audit');

    if (app()->environment(['local', 'testing'])) {
        Route::view('dev/ui', 'dev.ui')->name('dev.ui');
    }
});

require __DIR__.'/settings.php';
