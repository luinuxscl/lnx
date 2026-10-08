<?php

use App\Enums\Role;
use App\Livewire\Users\Create;
use App\Livewire\Users\Edit;
use App\Livewire\Users\Index;
use App\Livewire\Users\Invitations;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'role:'.Role::Root->value.'|'.Role::Admin->value,
])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::livewire('/', Index::class)->name('index')->menu([
        'label' => 'Users',
        'icon' => 'users',
        'group' => 'Administration',
        'group_icon' => 'cog-6-tooth',
        'audiences' => ['root', 'admin'],
        'roles' => ['root', 'admin'],
    ]);
    Route::livewire('create', Create::class)->name('create');
    Route::livewire('invitations', Invitations::class)->name('invitations');
    Route::livewire('{user}/edit', Edit::class)->name('edit');
});
