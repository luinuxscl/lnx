<?php

use App\Enums\Role;
use App\Livewire\Users\Create;
use App\Livewire\Users\Edit;
use App\Livewire\Users\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('redirects guests to the login page', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

it('forbids users without an admin role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
});

it('lets admins and root users view the users list', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee($admin->name);

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $this->actingAs($root)
        ->get(route('admin.users.index'))
        ->assertOk();
});

it('lets admins create a verified user with a role', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', Role::Admin->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasRole(Role::Admin))->toBeTrue();
});

it('never allows creating a superuser', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('name', 'New Root')
        ->set('email', 'new-root@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', Role::Root->value)
        ->call('save')
        ->assertHasErrors('role');

    expect(User::where('email', 'new-root@example.com')->exists())->toBeFalse();
});

it('lets admins update a user and change their role', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['user' => $user])
        ->set('name', 'Updated Name')
        ->set('role', Role::Admin->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->hasRole(Role::Admin))->toBeTrue();
});

it('lets admins remove a role from a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['user' => $user])
        ->set('role', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->refresh()->hasRole(Role::Admin))->toBeFalse();
});

it('never allows editing a superuser', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $root))
        ->assertForbidden();
});

it('never allows deleting a superuser', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    try {
        Livewire::actingAs($admin)->test(Index::class)->call('deleteUser', $root->id);
    } catch (Throwable) {
        // Expected: the action aborts with a 403.
    }

    expect(User::find($root->id))->not->toBeNull();
});

it('never allows deleting your own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    try {
        Livewire::actingAs($admin)->test(Index::class)->call('deleteUser', $admin->id);
    } catch (Throwable) {
        // Expected: the action aborts with a 403.
    }

    expect(User::find($admin->id))->not->toBeNull();
});

it('lets admins delete another user', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $user = User::factory()->create();

    Livewire::actingAs($admin)->test(Index::class)->call('deleteUser', $user->id);

    expect(User::find($user->id))->toBeNull();
});

it('shows the users menu entry only to root and admins', function () {
    $regular = User::factory()->create();

    $this->actingAs($regular)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Usuarios');

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Usuarios');
});
