<?php

namespace App\Livewire\Notifications;

use App\Enums\Role;
use App\Models\SupportTicket;
use App\Notifications\NewInternalMessage;
use App\Notifications\RoleChanged;
use App\Notifications\SupportTicketUpdated;
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
        if ($notification->type === SupportTicketUpdated::class) {
            return match ($notification->data['action'] ?? '') {
                'created' => __('A new support request was created.'),
                'reply' => __('A support request has a new reply.'),
                'status' => __('A support request changed status.'),
                'reopened' => __('A support request was reopened.'),
                default => __('A support request was updated.'),
            };
        }
        if ($notification->type === NewInternalMessage::class) {
            $sender = $notification->data['sender_name'] ?? __('Unknown');
            $preview = $notification->data['body_preview'] ?? '';

            return match ($notification->data['type'] ?? '') {
                'announcement' => __('Announcement from :sender: :preview', ['sender' => $sender, 'preview' => $preview]),
                default => __('New message from :sender: :preview', ['sender' => $sender, 'preview' => $preview]),
            };
        }
        if ($notification->type !== RoleChanged::class) {
            return __('You have a new notification.');
        }

        $old = $this->roleLabel($notification->data['previous_role'] ?? null);
        $new = $this->roleLabel($notification->data['new_role'] ?? null);

        return __('Your role changed from :previous to :new.', ['previous' => $old, 'new' => $new]);
    }

    public function messageLink(DatabaseNotification $notification): ?string
    {
        if ($notification->type !== NewInternalMessage::class) {
            return null;
        }

        $conversationId = $notification->data['conversation_id'] ?? null;

        return $conversationId ? route('messages.index', ['conversation' => $conversationId]) : route('messages.index');
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
