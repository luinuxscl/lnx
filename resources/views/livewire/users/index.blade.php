<section class="w-full space-y-6">
    <div class="grid grid-cols-12 gap-4">
        <x-lnx-ui::stat-card
            class="col-span-12 md:col-span-6 xl:col-span-3"
            :label="__('Total users')"
            :value="$stats['total']"
            icon="users"
            color="blue"
            :href="route('admin.users.index')"
            wire:navigate
            :active="$status === 'all' && $role === 'all'"
        />
        <x-lnx-ui::stat-card
            class="col-span-12 md:col-span-6 xl:col-span-3"
            :label="__('Active users')"
            :value="$stats['active']"
            icon="check-circle"
            color="emerald"
            :href="route('admin.users.index', ['status' => 'active'])"
            wire:navigate
            :active="$status === 'active'"
        />
        <x-lnx-ui::stat-card
            class="col-span-12 md:col-span-6 xl:col-span-3"
            :label="__('Suspended users')"
            :value="$stats['suspended']"
            icon="no-symbol"
            color="amber"
            :href="route('admin.users.index', ['status' => 'suspended'])"
            wire:navigate
            :active="$status === 'suspended'"
        />
        <x-lnx-ui::stat-card
            class="col-span-12 md:col-span-6 xl:col-span-3"
            :label="__('Pending invitations')"
            :value="$stats['pendingInvitations']"
            icon="envelope"
            color="violet"
            :href="route('admin.users.invitations')"
            wire:navigate
        />
    </div>

    <x-lnx-ui::data-table
        :title="__('Users')"
        :paginate="$users"
        table-class="min-w-[44rem]"
    >
        <x-slot:toolbar>
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <flux:select wire:model.live="status" class="sm:w-40" :aria-label="__('Status')">
                    <flux:select.option value="all">{{ __('All') }}</flux:select.option>
                    <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                    <flux:select.option value="suspended">{{ __('Suspended') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="role" class="sm:w-44" :aria-label="__('Role')">
                    <flux:select.option value="all">{{ __('All roles') }}</flux:select.option>
                    <flux:select.option value="root">{{ __(\App\Enums\Role::Root->label()) }}</flux:select.option>
                    <flux:select.option value="admin">{{ __(\App\Enums\Role::Admin->label()) }}</flux:select.option>
                    <flux:select.option value="none">{{ __('Standard user') }}</flux:select.option>
                </flux:select>

                <div class="w-full sm:w-64">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        :placeholder="__('Search users...')"
                        icon="magnifying-glass"
                    />
                </div>
            </div>
        </x-slot:toolbar>

        <x-slot:actions>
            @if ($this->hasActiveFilters())
                <flux:button icon="x-mark" wire:click="clearFilters">{{ __('Clear filters') }}</flux:button>
            @endif
            <flux:button :href="route('admin.users.invitations')" wire:navigate>{{ __('Invitations') }}</flux:button>
            <flux:button variant="primary" icon="plus" :href="route('admin.users.create')" wire:navigate>{{ __('Invite user') }}</flux:button>
        </x-slot:actions>

        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Registered') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell variant="strong">
                        <div class="flex items-center gap-3">
                            <x-user-avatar :user="$user" size="sm" />
                            <div class="min-w-0">
                                <div class="text-base font-semibold text-zinc-900 dark:text-white">{{ $user->name }}</div>
                                <div class="text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                    @if ($user->hasRole(\App\Enums\Role::Root))
                                        {{ __(\App\Enums\Role::Root->label()) }}
                                    @elseif ($user->hasRole(\App\Enums\Role::Admin))
                                        {{ __(\App\Enums\Role::Admin->label()) }}
                                    @else
                                        {{ __('Standard user') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($user->isSuspended())
                            <span class="inline-flex" title="{{ __('Suspended') }}">
                                <flux:icon icon="pause-circle" class="size-5 text-amber-500" />
                                <span class="sr-only">{{ __('Suspended') }}</span>
                            </span>
                        @else
                            <span class="inline-flex" title="{{ __('Active') }}">
                                <flux:icon icon="check-circle" class="size-5 text-emerald-500" />
                                <span class="sr-only">{{ __('Active') }}</span>
                            </span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-zinc-500 dark:text-zinc-400">{{ $user->created_at->format('d/m/Y') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        @if ($user->hasRole(\App\Enums\Role::Root))
                            <flux:text>{{ __('Managed outside the app') }}</flux:text>
                        @else
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('admin.users.edit', $user)" wire:navigate :aria-label="__('Edit')" />

                                @unless ($user->is(auth()->user()))
                                    <flux:dropdown align="end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" :aria-label="__('Actions')" />
                                        <flux:menu>
                                            @if ($user->isSuspended())
                                                <flux:menu.item icon="arrow-path" wire:click="confirmStatusChange({{ $user->id }}, 'reactivate')">
                                                    {{ __('Reactivate') }}
                                                </flux:menu.item>
                                            @else
                                                <flux:menu.item icon="pause-circle" wire:click="confirmStatusChange({{ $user->id }}, 'suspend')">
                                                    {{ __('Suspend') }}
                                                </flux:menu.item>
                                            @endif
                                            <flux:menu.item
                                                variant="danger"
                                                icon="trash"
                                                wire:click="deleteUser({{ $user->id }})"
                                                wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                                            >
                                                {{ __('Delete') }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                @endunless
                            </div>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <div class="flex flex-col items-center gap-3 py-12 text-center">
                            <span class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800" aria-hidden="true">
                                <flux:icon icon="users" class="size-6 text-zinc-400" />
                            </span>
                            <flux:text class="font-medium">{{ __('No users match your filters.') }}</flux:text>
                            @if ($this->hasActiveFilters())
                                <flux:button size="sm" variant="ghost" wire:click="clearFilters">{{ __('Clear filters') }}</flux:button>
                            @else
                                <flux:button size="sm" variant="primary" icon="plus" :href="route('admin.users.create')" wire:navigate>{{ __('Invite user') }}</flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </x-lnx-ui::data-table>

    <flux:modal name="confirm-user-status-change" wire:model.self="showStatusModal" @close="closeStatusModal" class="max-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">
                    @if ($pendingStatusAction === 'suspend')
                        {{ __('Suspend :name?', ['name' => $pendingUserName]) }}
                    @else
                        {{ __('Reactivate :name?', ['name' => $pendingUserName]) }}
                    @endif
                </flux:heading>

                <flux:text>
                    @if ($pendingStatusAction === 'suspend')
                        {{ __('Their access will be revoked immediately. Their data and settings will be kept.') }}
                    @else
                        {{ __('They can sign in again. Previous sessions and remember-me cookies will remain invalid.') }}
                    @endif
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="outline" wire:click="closeStatusModal">{{ __('Cancel') }}</flux:button>
                <flux:button
                    :variant="$pendingStatusAction === 'suspend' ? 'danger' : 'primary'"
                    wire:click="applyStatusChange"
                    wire:loading.attr="disabled"
                    wire:target="applyStatusChange"
                >
                    {{ $pendingStatusAction === 'suspend' ? __('Suspend') : __('Reactivate') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
