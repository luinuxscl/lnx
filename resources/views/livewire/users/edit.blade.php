<section class="w-full">
    <x-lnx-ui::page-header
        :title="__('Edit user')"
        icon="pencil-square"
        color="blue"
    />

    <form wire:submit="save" class="mt-6 w-full max-w-lg space-y-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

        <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            :description="__('Leave blank to keep the current password.')"
            autocomplete="new-password"
            viewable
        />

        <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" autocomplete="new-password" />

        <flux:select wire:model="role" :label="__('Role')">
            <flux:select.option value="">{{ __('No role') }}</flux:select.option>

            @foreach (\App\Enums\Role::assignable() as $assignableRole)
                <flux:select.option value="{{ $assignableRole->value }}">{{ __($assignableRole->label()) }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>

            <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>