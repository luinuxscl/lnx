<?php

namespace App\Livewire\Notifications;

use App\Enums\Role;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\RoleChanged;
use App\Notifications\SupportTicketUpdated;
use App\Support\Appearance;
use App\Support\Help\HelpCatalog;
use App\Support\Notifications\NotificationTypeRegistry;
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
        $declared = app(NotificationTypeRegistry::class)->message($notification);

        if ($declared !== null) {
            return $declared;
        }

        if ($notification->type === SupportTicketUpdated::class) {
            return match ($notification->data['action'] ?? '') {
                'created' => __('A new support request was created.'),
                'reply' => __('A support request has a new reply.'),
                'status' => __('A support request changed status.'),
                'reopened' => __('A support request was reopened.'),
                default => __('A support request was updated.'),
            };
        }
        if ($notification->type !== RoleChanged::class) {
            return __('You have a new notification.');
        }

        $old = $this->roleLabel($notification->data['previous_role'] ?? null);
        $new = $this->roleLabel($notification->data['new_role'] ?? null);

        return __('Your role changed from :previous to :new.', ['previous' => $old, 'new' => $new]);
    }

    public function supportLink(DatabaseNotification $notification): ?string
    {
        if ($notification->type !== SupportTicketUpdated::class) {
            return null;
        }

        $query = SupportTicket::query()->whereKey($notification->data['ticket_id'] ?? 0);
        if (! auth()->user()->hasRole(Role::Root)) {
            $query->where('user_id', auth()->id());
        }

        return $query->exists() ? route('support.show', $notification->data['ticket_id']) : null;
    }

    /**
     * The help center entry related to a notification: the slug declared by the
     * module that sent it, or the support guide for the kit's own ticket
     * notifications. Null when the entry does not exist or is restricted.
     */
    public function helpLink(DatabaseNotification $notification): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $slug = app(NotificationTypeRegistry::class)->helpTopic($notification);

        if ($slug === null && $notification->type === SupportTicketUpdated::class) {
            $slug = 'lnx.feedback-support';
        }

        if ($slug === null) {
            return null;
        }

        return app(HelpCatalog::class)->find($slug, $user) === null
            ? null
            : route('help.index', ['topic' => $slug]);
    }

    public function moduleLink(DatabaseNotification $notification): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        return app(NotificationTypeRegistry::class)->url($notification, $user);
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
        ])->layout('layouts.app');
    }
}
