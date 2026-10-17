<?php

use App\Actions\CreateVerifiedUser;
use App\Enums\Role;
use App\Livewire\Users\Create;
use App\Livewire\Users\CreateDirect;
use App\Livewire\Users\Edit;
use App\Livewire\Users\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
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

it('filters the user list by role and status', function () {
    $admin = User::factory()->create(['name' => 'Ada Admin']);
    $admin->assignRole(Role::Admin->value);

    User::factory()->create(['name' => 'Plain User']);
    User::factory()->create(['name' => 'Sam Suspended', 'suspended_at' => now()]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('role', 'none')
        ->assertSee('Plain User')
        ->assertDontSee('Ada Admin');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('status', 'suspended')
        ->assertSee('Sam Suspended')
        ->assertDontSee('Plain User');
});

it('shows the quick filters as clickable stat cards', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['status' => 'active']))
        ->assertOk()
        ->assertSee(__('Active users'))
        ->assertSee(__('Pending invitations'))
        ->assertSee(route('admin.users.invitations'));
});

it('lets admins invite a user without creating their account', function () {
    Notification::fake();
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('email', 'jane@example.com')
        ->set('role', Role::Admin->value)
        ->set('locale', 'es')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.invitations'));

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
    $this->assertDatabaseHas('user_invitations', ['email' => 'jane@example.com', 'role' => Role::Admin->value]);
});

it('never allows inviting a superuser', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('email', 'new-root@example.com')
        ->set('role', Role::Root->value)
        ->call('save')
        ->assertHasErrors('role');

    $this->assertDatabaseMissing('user_invitations', ['email' => 'new-root@example.com']);
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

it('sorts the user list by name in both directions', function () {
    $admin = User::factory()->create(['name' => 'Zed Admin']);
    $admin->assignRole(Role::Admin->value);

    User::factory()->create(['name' => 'Alice Anderson']);
    User::factory()->create(['name' => 'Bob Brown']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('sortBy', 'name')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['Alice Anderson', 'Bob Brown']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('sortBy', 'name')
        ->set('sortDirection', 'desc')
        ->assertSeeInOrder(['Zed Admin', 'Bob Brown']);
});

it('sorts the user list by registration date', function () {
    $admin = User::factory()->create(['name' => 'Admin Lead', 'created_at' => now()->subDays(10)]);
    $admin->assignRole(Role::Admin->value);

    User::factory()->create(['name' => 'Older User', 'created_at' => now()->subDays(5)]);
    User::factory()->create(['name' => 'Newer User', 'created_at' => now()]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('sortBy', 'registered')
        ->set('sortDirection', 'asc')
        ->assertSeeInOrder(['Admin Lead', 'Older User', 'Newer User']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('sortBy', 'registered')
        ->set('sortDirection', 'desc')
        ->assertSeeInOrder(['Newer User', 'Older User']);
});

it('ignores an unknown sort column', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('sortBy', 'password')
        ->assertSet('sortBy', 'name');
});

it('normalizes the page size to an allowed value', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('perPage', 999)
        ->assertSet('perPage', 10);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('perPage', 25)
        ->assertSet('perPage', 25);
});

it('filters the user list by email verification', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    User::factory()->create(['name' => 'Verified One']);
    User::factory()->unverified()->create(['name' => 'Unverified One']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('verification', 'verified')
        ->assertSee('Verified One')
        ->assertDontSee('Unverified One');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('verification', 'unverified')
        ->assertSee('Unverified One')
        ->assertDontSee('Verified One');
});

it('suspends multiple users in bulk skipping root and the current account', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $first = User::factory()->create();
    $second = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('selected', [$first->id, $second->id, $root->id, $admin->id])
        ->call('confirmBulkAction', 'suspend')
        ->assertSet('showBulkModal', true)
        ->call('applyBulkAction')
        ->assertSet('selected', []);

    expect($first->refresh()->isSuspended())->toBeTrue()
        ->and($second->refresh()->isSuspended())->toBeTrue()
        ->and($root->refresh()->isSuspended())->toBeFalse()
        ->and($admin->refresh()->isSuspended())->toBeFalse();
});

it('deletes multiple users in bulk skipping root and the current account', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $first = User::factory()->create();
    $second = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('selected', [$first->id, $second->id, $root->id, $admin->id])
        ->call('confirmBulkAction', 'delete')
        ->call('applyBulkAction');

    expect(User::find($first->id))->toBeNull()
        ->and(User::find($second->id))->toBeNull()
        ->and(User::find($root->id))->not->toBeNull()
        ->and(User::find($admin->id))->not->toBeNull();
});

it('exports the filtered user list as csv', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    User::factory()->create(['name' => 'Exported Person', 'email' => 'exported@example.com']);
    User::factory()->create(['name' => 'Hidden Person']);

    $response = $this->actingAs($admin)->get(route('admin.users.export', ['q' => 'Exported']));

    $response->assertOk();

    expect($response->streamedContent())
        ->toContain('Exported Person')
        ->toContain('exported@example.com')
        ->not->toContain('Hidden Person');
});

it('forbids users without an admin role from exporting', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users.export'))->assertForbidden();
});

it('redirects guests from the export route to login', function () {
    $this->get(route('admin.users.export'))->assertRedirect(route('login'));
});

it('does not let admins change a user email', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $user = User::factory()->create(['email' => 'original@example.com']);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['user' => $user])
        ->set('name', 'Renamed')
        ->set('email', 'tampered@example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    $user->refresh();

    expect($user->name)->toBe('Renamed')
        ->and($user->email)->toBe('original@example.com');
});

it('lets root change a user email and keeps the verification status', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $user = User::factory()->create(['email' => 'original@example.com', 'email_verified_at' => now()]);

    Livewire::actingAs($root)
        ->test(Edit::class, ['user' => $user])
        ->set('email', 'changed@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->email)->toBe('changed@example.com')
        ->and($user->email_verified_at)->not->toBeNull();
});

it('lets root create a verified user directly', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    Livewire::actingAs($root)
        ->test(CreateDirect::class)
        ->set('name', 'Direct Person')
        ->set('email', 'DIRECT@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', Role::Admin->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.index'));

    $created = User::where('email', 'direct@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->hasVerifiedEmail())->toBeTrue()
        ->and($created->hasRole(Role::Admin))->toBeTrue();
});

it('forbids admins from using the direct creation', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee('create-user', false);

    expect($admin->can('createDirectly', User::class))->toBeFalse();
});

it('shows the direct creation entry only to root', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee('create-user', false);

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $this->actingAs($root)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('create-user', false);
});

it('rejects a direct creation attempted by a non-superuser action call', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    try {
        app(CreateVerifiedUser::class)->handle($admin, 'Ghost', 'ghost@example.com', 'password');
    } catch (Throwable) {
        // Expected: the action aborts with a 403.
    }

    expect(User::where('email', 'ghost@example.com')->exists())->toBeFalse();
});

it('renders the direct creation modal for root', function () {
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    Livewire::actingAs($root)
        ->test(CreateDirect::class)
        ->assertSee(__('The account will be created already verified.'));
});

it('shows the email as read only to admins and editable to root', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $user))
        ->assertOk()
        ->assertSee(__('Only a superuser can change the email address.'));

    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    $this->actingAs($root)
        ->get(route('admin.users.edit', $user))
        ->assertOk()
        ->assertDontSee(__('Only a superuser can change the email address.'));
});
