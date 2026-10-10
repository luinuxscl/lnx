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

    <div class="relative space-y-4">
        <div
            wire:loading
            wire:target="search,status,role,verification,perPage,clearFilters,sort,selectPage,clearSelection,applyStatusChange,applyBulkAction,deleteUser"
            class="absolute inset-0 z-20 flex items-start justify-center rounded-2xl bg-white/70 pt-40 backdrop-blur-sm dark:bg-zinc-900/70"
        >
            <flux:icon icon="arrow-path" class="size-8 animate-spin text-zinc-500" />
        </div>

        @if (count($selected) > 0)
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="font-medium">{{ __(':count selected', ['count' => count($selected)]) }}</flux:text>
                <flux:spacer />
                <flux:button size="sm" icon="arrow-path" wire:click="confirmBulkAction('reactivate')">{{ __('Reactivate') }}</flux:button>
                <flux:button size="sm" icon="pause-circle" wire:click="confirmBulkAction('suspend')">{{ __('Suspend') }}</flux:button>
                <flux:button size="sm" variant="danger" icon="trash" wire:click="confirmBulkAction('delete')">{{ __('Delete') }}</flux:button>
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearSelection">{{ __('Clear selection') }}</flux:button>
            </div>
        @endif

        <x-lnx-ui::data-table
            :paginate="$users"
            table-class="min-w-[44rem]"
        >
            <x-slot:toolbar>
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <flux:select wire:model.live="status" class="sm:w-36" :aria-label="__('Status')">
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

                    <flux:select wire:model.live="verification" class="sm:w-44" :aria-label="__('Email verification')">
                        <flux:select.option value="all">{{ __('All') }}</flux:select.option>
                        <flux:select.option value="verified">{{ __('Verified') }}</flux:select.option>
                        <flux:select.option value="unverified">{{ __('Unverified') }}</flux:select.option>
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
                <div class="w-24">
                    <flux:select wire:model.live="perPage" :aria-label="__('Per page')">
                        @foreach ($perPageOptions as $option)
                            <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button icon="arrow-down-tray" :href="$this->exportUrl()">{{ __('Export CSV') }}</flux:button>
                <flux:button :href="route('admin.users.invitations')" wire:navigate>{{ __('Invitations') }}</flux:button>
                <flux:button variant="primary" icon="plus" :href="route('admin.users.create')" wire:navigate>{{ __('Invite user') }}</flux:button>
            </x-slot:actions>

            <flux:table.columns>
                <flux:table.column class="w-10">
                    <flux:checkbox
                        x-data="{ ids: @js($pageIds) }"
                        x-bind:checked="ids.length > 0 && ids.every(id => ($wire.selected ?? []).includes(id))"
                        x-on:change="$wire.set('selected', $event.target.checked ? ids : [])"
                        :aria-label="__('Select all')"
                    />
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')" class="max-lg:hidden">{{ __('Email') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">{{ __('Status') }}</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'registered'" :direction="$sortDirection" wire:click="sort('registered')" class="max-lg:hidden">{{ __('Registered') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>
                            <flux:checkbox
                                wire:model.live="selected"
                                value="{{ $user->id }}"
                                :disabled="$user->is(auth()->user()) || $user->hasRole(\App\Enums\Role::Root)"
                                :aria-label="__('Select :name', ['name' => $user->name])"
                            />
                        </flux:table.cell>
                        <flux:table.cell variant="strong">
                            <div class="flex items-center gap-3">
                                <x-user-avatar :user="$user" size="sm" />
                                <div class="min-w-0">
                                    <div class="text-base font-semibold text-zinc-900 dark:text-white">
                                        @if ($user->hasRole(\App\Enums\Role::Root))
                                            {{ $user->name }}
                                        @else
                                            <a href="{{ route('admin.users.edit', $user) }}" wire:navigate class="rounded hover:underline focus-visible:underline focus-visible:outline-none">{{ $user->name }}</a>
                                        @endif
                                    </div>
                                    <div class="text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                        @if ($user->hasRole(\App\Enums\Role::Root))
                                            {{ __(\App\Enums\Role::Root->label()) }}
                                        @elseif ($user->hasRole(\App\Enums\Role::Admin))
                                            {{ __(\App\Enums\Role::Admin->label()) }}
                                        @else
                                            {{ __('Standard user') }}
                                        @endif
                                    </div>
                                    <div class="text-xs text-zinc-500 lg:hidden dark:text-zinc-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="max-lg:hidden">
                            <div class="flex items-center gap-2">
                                <span class="truncate">{{ $user->email }}</span>
                                @if ($user->email_verified_at !== null)
                                    <flux:icon icon="check-badge" variant="mini" class="size-4 shrink-0 text-emerald-500" :title="__('Email verified')" />
                                @else
                                    <flux:icon icon="exclamation-triangle" variant="mini" class="size-4 shrink-0 text-amber-500" :title="__('Email not verified')" />
                                @endif
                            </div>
                        </flux:table.cell>
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
                        <flux:table.cell class="text-zinc-500 max-lg:hidden dark:text-zinc-400">{{ $user->created_at->format('d/m/Y') }}</flux:table.cell>
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
                        <flux:table.cell colspan="6">
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
    </div>

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

    <flux:modal name="confirm-bulk-action" wire:model.self="showBulkModal" @close="closeBulkModal" class="max-w-md">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">
                    @if ($pendingBulkAction === 'delete')
                        {{ __('Delete :count selected users?', ['count' => count($selected)]) }}
                    @elseif ($pendingBulkAction === 'suspend')
                        {{ __('Suspend :count selected users?', ['count' => count($selected)]) }}
                    @else
                        {{ __('Reactivate :count selected users?', ['count' => count($selected)]) }}
                    @endif
                </flux:heading>

                <flux:text>
                    @if ($pendingBulkAction === 'delete')
                        {{ __('This action cannot be undone. Root accounts and your own account are skipped.') }}
                    @elseif ($pendingBulkAction === 'suspend')
                        {{ __('Their access will be revoked immediately. Root accounts and your own account are skipped.') }}
                    @else
                        {{ __('They can sign in again. Root accounts and your own account are skipped.') }}
                    @endif
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button variant="outline" wire:click="closeBulkModal">{{ __('Cancel') }}</flux:button>
                <flux:button
                    :variant="$pendingBulkAction === 'reactivate' ? 'primary' : 'danger'"
                    wire:click="applyBulkAction"
                    wire:loading.attr="disabled"
                    wire:target="applyBulkAction"
                >
                    @if ($pendingBulkAction === 'delete')
                        {{ __('Delete') }}
                    @elseif ($pendingBulkAction === 'suspend')
                        {{ __('Suspend') }}
                    @else
                        {{ __('Reactivate') }}
                    @endif
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
