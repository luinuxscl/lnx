<section class="w-full">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading level="1">{{ __('Invitations') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.users.create')" wire:navigate>{{ __('Invite user') }}</flux:button>
    </div>

    @error('invitation') <flux:callout variant="danger" class="mt-4">{{ $message }}</flux:callout> @enderror

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search invitations...')" icon="magnifying-glass" class="mt-6 max-w-sm" />

    <div class="mt-6 overflow-x-auto">
        <flux:table :paginate="$invitations" class="min-w-[42rem]">
            <flux:table.columns>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column>{{ __('Expires') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($invitations as $invitation)
                    <flux:table.row :key="$invitation->id">
                        <flux:table.cell>{{ $invitation->email }}</flux:table.cell>
                        <flux:table.cell>{{ $invitation->role ? __(\App\Enums\Role::from($invitation->role)->label()) : __('No role') }}</flux:table.cell>
                        <flux:table.cell>{{ $invitation->expires_at->format('Y-m-d H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ __($invitation->status()) }}</flux:table.cell>
                        <flux:table.cell align="end">
                            @if ($invitation->accepted_at === null && $invitation->revoked_at === null)
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="resend({{ $invitation->id }})" wire:confirm="{{ __('Resend this invitation?') }}">{{ __('Resend') }}</flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="revoke({{ $invitation->id }})" wire:confirm="{{ __('Revoke this invitation?') }}">{{ __('Revoke') }}</flux:button>
                                </div>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="5">{{ __('No invitations found.') }}</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
