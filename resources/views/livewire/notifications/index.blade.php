<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('Notifications')"
        :subtitle="__('Your account notifications are saved here.')"
        icon="bell"
        color="amber"
    >
        @if ($unreadCount > 0)
            <x-slot:actions>
                <flux:button wire:click="markAllAsRead" variant="ghost">{{ __('Mark all as read') }}</flux:button>
            </x-slot:actions>
        @endif
    </x-lnx-ui::page-header>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <article wire:key="notification-{{ $notification->id }}" class="rounded-lg border p-4 {{ $notification->read_at === null ? 'border-[var(--color-accent)] bg-zinc-50 dark:bg-zinc-900' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }}">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <flux:badge :color="$notification->read_at === null ? 'amber' : 'zinc'">{{ $notification->read_at === null ? __('Unread') : __('Read') }}</flux:badge>
                        <flux:text>{{ $this->message($notification) }}</flux:text>
                        @if ($supportLink = $this->supportLink($notification))
                            <flux:button size="sm" :href="$supportLink" wire:navigate>{{ __('View request') }}</flux:button>
                        @endif
                        @if ($moduleLink = $this->moduleLink($notification))
                            <flux:button size="sm" :href="$moduleLink" wire:navigate>{{ __('View') }}</flux:button>
                        @endif
                        <flux:text class="text-xs"><time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->copy()->timezone($timezone)->format('Y-m-d H:i') }}</time> · {{ $timezone }}</flux:text>
                    </div>

                    @if ($notification->read_at === null)
                        <flux:button size="sm" wire:click="markAsRead('{{ $notification->id }}')" :aria-label="__('Mark notification as read')">{{ __('Mark as read') }}</flux:button>
                    @endif
                </div>
            </article>
        @empty
            <flux:card><flux:text>{{ __('You have no notifications yet.') }}</flux:text></flux:card>
        @endforelse
    </div>

    {{ $notifications->links() }}
</section>
