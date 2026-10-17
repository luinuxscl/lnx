<flux:modal name="invite-user" class="max-w-lg">
    <div class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('Invite user') }}</flux:heading>
            <flux:text>{{ __('The recipient will choose their name and password.') }}</flux:text>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="email" :label="__('Email')" type="email" required autofocus autocomplete="email" />

            <flux:select wire:model="role" :label="__('Role')">
                <flux:select.option value="">{{ __('No role') }}</flux:select.option>
                @foreach (\App\Enums\Role::assignable() as $assignableRole)
                    <flux:select.option value="{{ $assignableRole->value }}">{{ __($assignableRole->label()) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="locale" :label="__('Invitation language')">
                <flux:select.option value="es">{{ __('Spanish') }}</flux:select.option>
                <flux:select.option value="en">{{ __('English') }}</flux:select.option>
            </flux:select>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Send invitation') }}</flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </form>
    </div>
</flux:modal>
