<?php

namespace App\Livewire\Notifications;

use App\Enums\Role;
use App\Notifications\RoleChanged;
use App\Support\Appearance;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Notifications')]
class Index extends Component
{
    use WithPagination;

    public function markAsRead(string $id): void
    {
        $notification = auth()->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $this->dispatch('notifications-updated');
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        $this->dispatch('notifications-updated');
    }

    public function message(DatabaseNotification $notification): string
    {
        if ($notification->type !== RoleChanged::class) {
            return __('You have a new notification.');
        }

        $old = $this->roleLabel($notification->data['previous_role'] ?? null);
        $new = $this->roleLabel($notification->data['new_role'] ?? null);

        return __('Your role changed from :previous to :new.', ['previous' => $old, 'new' => $new]);
    }

    private function roleLabel(mixed $value): string
    {
        if (! is_string($value)) {
            return __('No role');
        }

        $role = Role::tryFrom($value);

        return $role === null ? __('Unknown role') : __($role->label());
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.notifications.index', [
            'notifications' => $user->notifications()->reorder()->orderByDesc('created_at')->orderByDesc('id')->paginate(10),
            'unreadCount' => $user->unreadNotifications()->count(),
            'timezone' => Appearance::currentTimezone($user),
        ])->layout('layouts.app', [
            'breadcrumbs' => [['label' => __('Notifications')]],
        ]);
    }
}
