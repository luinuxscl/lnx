<section class="w-full">
    <div class="flex items-center justify-between gap-4">
        <flux:heading level="1">{{ __('Users') }}</flux:heading>

        <flux:button variant="primary" icon="plus" :href="route('admin.users.create')" wire:navigate>
            {{ __('Create user') }}
        </flux:button>
    </div>

    <flux:input
        wire:model.live.debounce.300ms="search"
        :placeholder="__('Search users...')"
        icon="magnifying-glass"
        class="mt-6 max-w-sm"
    />

    <flux:table :paginate="$users" class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Role') }}</flux:table.column>
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
                    <flux:table.cell align="end">
                        @if ($user->hasRole(\App\Enums\Role::Root))
                            <flux:text>{{ __('Managed outside the app') }}</flux:text>
                        @else
                            <div class="flex items-center justify-end gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('admin.users.edit', $user)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>

                                @unless ($user->is(auth()->user()))
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
                    <flux:table.cell colspan="4">{{ __('No users found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>