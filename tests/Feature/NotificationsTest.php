<?php

use App\Enums\Role;
use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Index;
use App\Livewire\Users\Edit;
use App\Models\User;
use App\Notifications\RoleChanged;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('requires a current account and allows accounts without a role', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));

    $user = User::factory()->create();
    $this->actingAs($user)->get(route('notifications.index'))->assertSee('Aún no tienes notificaciones.');

    $user->forceFill(['suspended_at' => now()])->save();
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});

it('keeps a notification after signing out and back in', function () {
    $user = User::factory()->create();
    $user->notify(new RoleChanged(null, Role::Admin->value));

    $this->actingAs($user)->get(route('notifications.index'))->assertSee('Tu rol cambió de Sin rol a Administrador.');
    auth()->logout();
    $this->get(route('notifications.index'))->assertRedirect(route('login'));

    $this->actingAs($user)->get(route('notifications.index'))->assertSee('Tu rol cambió de Sin rol a Administrador.');
});

it('shows ten newest notifications per page without reading them', function () {
    $user = User::factory()->create();

    for ($number = 1; $number <= 12; $number++) {
        $this->travelTo(now()->addMinute());
        $user->notify(new RoleChanged(null, Role::Admin->value));
    }

    $first = $user->notifications()->reorder()->orderBy('created_at')->first();
    $latest = $user->notifications()->reorder()->orderByDesc('created_at')->first();

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertSee($latest->id)
        ->assertDontSee($first->id);
    $this->get(route('notifications.index', ['page' => 2]))->assertSee($first->id);

    expect($user->unreadNotifications()->count())->toBe(12);
});

it('marks only an owned notification as read and refreshes the bell', function () {
    $user = User::factory()->create();
    $user->notify(new RoleChanged(null, Role::Admin->value));
    $notice = $user->notifications()->firstOrFail();

    $bell = Livewire::actingAs($user)->test(Bell::class)->assertSee('1 sin leer');
    $this->actingAs($user)->get(route('dashboard'))->assertSee('1 sin leer');
    Livewire::actingAs($user)->test(Index::class)
        ->call('markAsRead', $notice->id)
        ->assertDispatched('notifications-updated');

    expect($notice->fresh()->read_at)->not->toBeNull();
    $bell->dispatch('notifications-updated')->assertDontSee('1 sin leer');
});

it('marks pending notifications on every page and leaves another account untouched', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    for ($number = 0; $number < 12; $number++) {
        $user->notify(new RoleChanged(null, Role::Admin->value));
    }
    $other->notify(new RoleChanged(null, Role::Admin->value));

    Livewire::actingAs($user)->test(Index::class)->call('markAllAsRead')->assertDispatched('notifications-updated');

    expect($user->unreadNotifications()->count())->toBe(0)
        ->and($other->unreadNotifications()->count())->toBe(1);
});

it('returns 404 for foreign or missing notification IDs even for root', function () {
    $this->seed(RolePermissionSeeder::class);
    $owner = User::factory()->create();
    $owner->notify(new RoleChanged(null, Role::Admin->value));
    $notice = $owner->notifications()->firstOrFail();
    $root = User::factory()->create();
    $root->assignRole(Role::Root);

    Livewire::actingAs($root)->test(Index::class)
        ->call('markAsRead', $notice->id)
        ->assertNotFound();

    Livewire::actingAs($root)->test(Index::class)
        ->call('markAsRead', '00000000-0000-0000-0000-000000000000')
        ->assertNotFound();

    expect($notice->fresh()->read_at)->toBeNull();
});

it('translates saved role values and displays dates in the account time zone', function () {
    $user = User::factory()->create(['locale' => 'es', 'timezone' => 'America/Santiago']);
    $this->travelTo(Carbon::parse('2026-10-02 12:00:00 UTC'));
    $user->notify(new RoleChanged(null, Role::Admin->value));

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertSee('Tu rol cambió de Sin rol a Administrador.')
        ->assertSee('2026-10-02 09:00');

    $user->update(['locale' => 'en', 'timezone' => 'UTC']);
    $this->get(route('notifications.index'))
        ->assertSee('Your role changed from No role to Administrator.')
        ->assertSee('2026-10-02 12:00');
});

it('renders unknown types safely without using their stored content', function () {
    $user = User::factory()->create();
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'Unknown\\Notification',
        'data' => ['message' => '<script>alert(1)</script>', 'url' => 'https://example.com/unsafe'],
    ]);

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertSee('Tienes una nueva notificación.')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('https://example.com/unsafe');
});

it('creates one role notice for assignment and removal, independent of the administrator', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $user = User::factory()->create();

    Livewire::actingAs($admin)->test(Edit::class, ['user' => $user])->set('role', Role::Admin->value)->call('save');
    Livewire::actingAs($admin)->test(Edit::class, ['user' => $user])->call('save');
    $this->travelTo(now()->addSecond());
    Livewire::actingAs($admin)->test(Edit::class, ['user' => $user])->set('role', '')->call('save');

    expect($user->notifications()->count())->toBe(2);
    expect($user->notifications()->get()->pluck('data')->all())->toContain(
        ['previous_role' => null, 'new_role' => Role::Admin->value],
        ['previous_role' => Role::Admin->value, 'new_role' => null],
    );

    $admin->delete();
    $this->actingAs($user)->get(route('notifications.index'))
        ->assertSee('Tu rol cambió de Administrador a Sin rol.');
});

it('rolls back the user, activity and notification when delivery fails', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    $user = User::factory()->create();
    $originalName = $user->name;

    DB::listen(function ($query): void {
        if (str_contains($query->sql, 'insert into "notifications"')) {
            throw new RuntimeException('Notification storage failed.');
        }
    });

    try {
        Livewire::actingAs($admin)->test(Edit::class, ['user' => $user])
            ->set('name', 'Changed name')
            ->set('role', Role::Admin->value)
            ->call('save');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Notification storage failed.');
    }

    expect($user->fresh()->name)->toBe($originalName)
        ->and($user->fresh()->primaryRole())->toBeNull()
        ->and($user->notifications()->count())->toBe(0);
    $this->assertDatabaseMissing('administrative_activities', ['resource_id' => $user->id, 'action' => 'user_role_changed']);
});
