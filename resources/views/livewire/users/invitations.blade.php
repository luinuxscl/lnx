<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('Invitations')"
        :subtitle="__('Invite new users and manage the status of sent invitations.')"
        icon="envelope"
        color="blue"
    >
        <x-slot:actions>
            <flux:modal.trigger name="invite-user">
                <flux:button variant="primary" icon="plus">{{ __('Invite user') }}</flux:button>
            </flux:modal.trigger>
        </x-slot:actions>
    </x-lnx-ui::page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <x-lnx-ui::stat-card
            :label="__('Pending')"
            :value="$stats['pending']"
            icon="envelope"
            color="violet"
            :href="route('admin.users.invitations', ['status' => 'pending'])"
            wire:navigate
            :active="$status === 'pending'"
        />
        <x-lnx-ui::stat-card
            :label="__('Accepted')"
            :value="$stats['accepted']"
            icon="check-circle"
            color="emerald"
            :href="route('admin.users.invitations', ['status' => 'accepted'])"
            wire:navigate
            :active="$status === 'accepted'"
        />
        <x-lnx-ui::stat-card
            :label="__('Expired')"
            :value="$stats['expired']"
            icon="clock"
            color="amber"
            :href="route('admin.users.invitations', ['status' => 'expired'])"
            wire:navigate
            :active="$status === 'expired'"
        />
        <x-lnx-ui::stat-card
            :label="__('Revoked')"
            :value="$stats['revoked']"
            icon="no-symbol"
            color="cyan"
            :href="route('admin.users.invitations', ['status' => 'revoked'])"
            wire:navigate
            :active="$status === 'revoked'"
        />
        <x-lnx-ui::stat-card
            :label="__('Send failed')"
            :value="$stats['sendFailed']"
            icon="exclamation-triangle"
            color="rose"
            :href="route('admin.users.invitations', ['status' => 'send_failed'])"
            wire:navigate
            :active="$status === 'send_failed'"
        />
    </div>

    @error('invitation')
        <flux:callout variant="danger" icon="exclamation-triangle">{{ $message }}</flux:callout>
    @enderror

    <div class="relative space-y-4">
        <div
            wire:loading
            wire:target="search,status,perPage,clearFilters,sort,resend,revoke"
            class="absolute inset-0 z-20 flex items-start justify-center rounded-2xl bg-white/70 pt-40 backdrop-blur-sm dark:bg-zinc-900/70"
        >
            <flux:icon icon="arrow-path" class="size-8 animate-spin text-zinc-500" />
        </div>

        <x-lnx-ui::data-table :paginate="$invitations">
            <x-slot:toolbar>
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <flux:select wire:model.live="status" class="sm:w-44" :aria-label="__('Status')">
                        <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
                        <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                        <flux:select.option value="accepted">{{ __('Accepted') }}</flux:select.option>
                        <flux:select.option value="expired">{{ __('Expired') }}</flux:select.option>
                        <flux:select.option value="revoked">{{ __('Revoked') }}</flux:select.option>
                        <flux:select.option value="send_failed">{{ __('Send failed') }}</flux:select.option>
                    </flux:select>

                    <div class="w-full sm:w-64">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            :placeholder="__('Search invitations...')"
                            icon="magnifying-glass"
                        />
                    </div>
                </div>
            </x-slot:toolbar>

            <x-slot:actions>
                @if ($this->hasActiveFilters())
                    <flux:button icon="x-mark" wire:click="clearFilters">{{ __('Clear filters') }}</flux:button>
                @endif
                <div class="w-24">
                    <flux:select wire:model.live="perPage" :aria-label="__('Per page')">
                        @foreach ($perPageOptions as $option)
                            <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </x-slot:actions>

            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')">{{ __('Email') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'role'" :direction="$sortDirection" wire:click="sort('role')" class="max-lg:hidden">{{ __('Role') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'expires'" :direction="$sortDirection" wire:click="sort('expires')" class="max-md:hidden">{{ __('Expires') }}</flux:table.column>
                <flux:table.column class="w-24">{{ __('Status') }}</flux:table.column>
                <flux:table.column align="end" class="w-20">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($invitations as $invitation)
                    @php
                        $state = $invitation->status();
                        $badgeColor = match ($state) {
                            'Accepted' => 'green',
                            'Pending' => 'blue',
                            'Expired' => 'amber',
                            'Revoked' => 'zinc',
                            default => 'red',
                        };
                    @endphp
                    <flux:table.row :key="$invitation->id">
                        <flux:table.cell variant="strong">
                            <x-lnx-ui::media-cell :title="$invitation->email" icon="envelope" color="blue" />
                        </flux:table.cell>
                        <flux:table.cell class="max-lg:hidden">
                            {{ $invitation->role ? __(\App\Enums\Role::from($invitation->role)->label()) : __('No role') }}
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap text-zinc-500 max-md:hidden dark:text-zinc-400">
                            {{ $invitation->expires_at->format('d/m/Y H:i') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$badgeColor">{{ __($state) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            @if ($invitation->accepted_at === null && $invitation->revoked_at === null)
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="resend({{ $invitation->id }})" wire:confirm="{{ __('Resend this invitation?') }}" :aria-label="__('Resend')" />
                                    <flux:button size="sm" variant="ghost" icon="no-symbol" wire:click="revoke({{ $invitation->id }})" wire:confirm="{{ __('Revoke this invitation?') }}" :aria-label="__('Revoke')" />
                                </div>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <div class="flex flex-col items-center gap-3 py-12 text-center">
                                <span class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800" aria-hidden="true">
                                    <flux:icon icon="envelope" class="size-6 text-zinc-400" />
                                </span>
                                <flux:text class="font-medium">
                                    {{ $this->hasActiveFilters() ? __('No invitations match your filters.') : __('No invitations found.') }}
                                </flux:text>
                                @if ($this->hasActiveFilters())
                                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">{{ __('Clear filters') }}</flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </x-lnx-ui::data-table>
    </div>

    <livewire:users.create />
</section>
