<section class="w-full">
    <div class="flex items-center justify-between gap-4">
        <flux:heading level="1">{{ __('Users') }}</flux:heading>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('admin.users.invitations')" wire:navigate>{{ __('Invitations') }}</flux:button>
            <flux:button variant="primary" icon="plus" :href="route('admin.users.create')" wire:navigate>{{ __('Invite user') }}</flux:button>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :placeholder="__('Search users...')"
            icon="magnifying-glass"
            class="max-w-sm"
        />
        <flux:select wire:model.live="status" :label="__('Status')" class="max-w-48">
            <flux:select.option value="all">{{ __('All') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="suspended">{{ __('Suspended') }}</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$users" class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Role') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($user->hasRole(\App\Enums\Role::Root))
                            <flux:badge color="zinc">{{ __(\App\Enums\Role::Root->label()) }}</flux:badge>
                        @elseif ($user->hasRole(\App\Enums\Role::Admin))
                            <flux:badge color="blue">{{ __(\App\Enums\Role::Admin->label()) }}</flux:badge>
                        @else
                            <flux:text>{{ __('No role') }}</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($user->isSuspended())
                            <flux:badge color="amber">{{ __('Suspended') }}</flux:badge>
                        @else
                            <flux:badge color="green">{{ __('Active') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @if ($user->hasRole(\App\Enums\Role::Root))
                            <flux:text>{{ __('Managed outside the app') }}</flux:text>
                        @else
                            <div class="flex items-center justify-end gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('admin.users.edit', $user)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>

                                @unless ($user->is(auth()->user()))
                                    @if ($user->isSuspended())
                                        <flux:button size="sm" variant="ghost" wire:click="confirmStatusChange({{ $user->id }}, 'reactivate')">
                                            {{ __('Reactivate') }}
                                        </flux:button>
                                    @else
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            wire:click="confirmStatusChange({{ $user->id }}, 'suspend')"
                                        >
                                            {{ __('Suspend') }}
                                        </flux:button>
                                    @endif
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                                    >
                                        {{ __('Delete') }}
                                    </flux:button>
                                @endunless
                            </div>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('No users found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

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
