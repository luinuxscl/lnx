<?php

use App\Enums\Role;
use App\Livewire\Users\Create;
use App\Livewire\Users\Edit;
use App\Livewire\Users\Index;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'role:'.Role::Root->value.'|'.Role::Admin->value,
])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::livewire('/', Index::class)->name('index');
    Route::livewire('create', Create::class)->name('create');
    Route::livewire('{user}/edit', Edit::class)->name('edit');
});
