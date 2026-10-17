<flux:modal name="create-user" class="max-w-lg">
    <div class="space-y-6">
        <div class="space-y-2">
            <flux:heading size="lg">{{ __('Create user') }}</flux:heading>
            <flux:text>{{ __('The account will be created already verified.') }}</flux:text>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" />

            <flux:select wire:model="role" :label="__('Role')">
                <flux:select.option value="">{{ __('No role') }}</flux:select.option>
                @foreach (\App\Enums\Role::assignable() as $assignableRole)
                    <flux:select.option value="{{ $assignableRole->value }}">{{ __($assignableRole->label()) }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Create user') }}</flux:button>
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </form>
    </div>
</flux:modal>
