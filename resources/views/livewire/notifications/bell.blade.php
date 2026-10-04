<div class="relative inline-flex">
    <flux:button
        :href="route('notifications.index')"
        wire:navigate
        icon="bell"
        variant="ghost"
        square
        :aria-label="$unreadCount > 0 ? __('Notifications, :count unread', ['count' => \Illuminate\Support\Number::format($unreadCount)]) : __('Notifications')"
    />
    @if ($unreadCount > 0)
        <span aria-hidden="true" class="pointer-events-none absolute -right-1 -top-1 rounded-full bg-[var(--color-accent)] px-1.5 text-xs font-semibold text-[var(--color-accent-foreground)]">{{ \Illuminate\Support\Number::format($unreadCount) }}</span>
    @endif
</div>
