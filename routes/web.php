<?php

use App\Http\Controllers\AcceptInvitationController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('invitations/{invitation}/{token}', [AcceptInvitationController::class, 'create'])->name('invitations.accept');
Route::post('invitations/{invitation}/{token}', [AcceptInvitationController::class, 'store'])
    ->middleware('throttle:10,1')->name('invitations.accept.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/users.php';
