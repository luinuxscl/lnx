<?php

use App\Enums\Role;
use App\Livewire\Notifications\Index;
use App\Models\User;
use App\Support\Notifications\NotificationTypeRegistry;
use App\Support\Permissions\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Livewire;
use Tests\Fixtures\Notifications\DocumentReadyNotification;

function registerDocumentNotification(?string $permission = null): void
{
    app(NotificationTypeRegistry::class)->register('demo', [
        DocumentReadyNotification::class => [
            'message' => fn (DatabaseNotification $notification): string => 'Document '.$notification->data['name'].' is ready.',
            'url' => fn (DatabaseNotification $notification): string => route('notifications.index', ['document' => $notification->data['document_id']]),
            'permission' => $permission,
        ],
    ]);
}

it('renders, links and marks as read a notification declared by a package', function () {
    registerDocumentNotification();

    $user = User::factory()->create();
    $user->notify(new DocumentReadyNotification(7, 'Lab results'));

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertSee('Document Lab results is ready.')
        ->assertSee('document=7');

    $notification = $user->notifications()->firstOrFail();
    Livewire::actingAs($user)->test(Index::class)
        ->call('markAsRead', $notification->id)
        ->assertDispatched('notifications-updated');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('hides the link when the identity lacks the declared permission', function () {
    app(PermissionRegistry::class)->register('demo', ['demo.view_document']);
    $this->seed(RolePermissionSeeder::class);

    registerDocumentNotification('demo.view_document');

    $member = User::factory()->create();
    $member->notify(new DocumentReadyNotification(7, 'Lab results'));

    $this->actingAs($member)->get(route('notifications.index'))
        ->assertSee('Document Lab results is ready.')
        ->assertDontSee('document=7');

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $admin->notify(new DocumentReadyNotification(7, 'Lab results'));

    $this->actingAs($admin)->get(route('notifications.index'))
        ->assertSee('document=7');
});

it('keeps the safe fallback for a type that is not declared', function () {
    $user = User::factory()->create();
    $user->notify(new DocumentReadyNotification(7, '<script>alert(1)</script>'));

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertSee('Tienes una nueva notificación.')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('document=7');
});

it('rejects a declaration that is not a notification class', function () {
    app(NotificationTypeRegistry::class)->register('demo', [
        'App\\Models\\User' => ['message' => fn (): string => 'Nope'],
    ]);
})->throws(InvalidArgumentException::class);

it('rejects a declaration without a callable message', function () {
    app(NotificationTypeRegistry::class)->register('demo', [
        DocumentReadyNotification::class => ['url' => fn (): string => 'https://example.com'],
    ]);
})->throws(InvalidArgumentException::class);
