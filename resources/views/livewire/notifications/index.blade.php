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

    <x-lnx-ui::filter-bar>
        <x-lnx-ui::filter-chip
            name="status"
            icon="bell"
            :label="__('Status')"
            :options="['unread' => __('Unread')]"
            :any-label="__('All')"
            :active="$status !== 'all'"
        />

        {{-- ms-auto en un wrapper: el ítem del flex es el wrapper del tooltip,
            y una clase en el botón interior no empuja nada. --}}
        <div class="ms-auto flex items-center gap-2">
            @if ($this->hasActiveFilters())
                <flux:button
                    size="sm"
                    square
                    icon="funnel-x"
                    :tooltip="__('Clear filters')"
                    :aria-label="__('Clear filters')"
                    wire:click="clearFilters"
                />
            @endif

            <x-lnx-ui::table-per-page :options="[10, 25, 50]" :aria-label="__('Per page')" />
        </div>
    </x-lnx-ui::filter-bar>

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
                        @if ($helpLink = $this->helpLink($notification))
                            <flux:button size="sm" variant="ghost" icon="lifebuoy" :href="$helpLink" wire:navigate>{{ __('Read the guide') }}</flux:button>
                        @endif
                        <flux:text class="text-xs"><time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->copy()->timezone($timezone)->format('Y-m-d H:i') }}</time> · {{ $timezone }}</flux:text>
                    </div>

                    @if ($notification->read_at === null)
                        <flux:button size="sm" wire:click="markAsRead('{{ $notification->id }}')" :aria-label="__('Mark notification as read')">{{ __('Mark as read') }}</flux:button>
                    @endif
                </div>
            </article>
        @empty
            <flux:card>
                <div class="flex flex-col items-center gap-3 py-12 text-center">
                    <span class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800" aria-hidden="true">
                        <flux:icon icon="bell" class="size-6 text-zinc-400" />
                    </span>
                    <flux:text class="font-medium">
                        {{ $this->hasActiveFilters() ? __('No unread notifications.') : __('You have no notifications yet.') }}
                    </flux:text>
                    @if ($this->hasActiveFilters())
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">{{ __('Clear filters') }}</flux:button>
                    @endif
                </div>
            </flux:card>
        @endforelse
    </div>

    {{ $notifications->links() }}
</section>
