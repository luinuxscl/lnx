<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <div class="my-6 space-y-4">
            <flux:heading level="3">{{ __('Avatar') }}</flux:heading>

            <div class="flex items-center gap-4">
                @if ($avatar && in_array($avatar->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true))
                    <img src="{{ $avatar->temporaryUrl() }}" alt="{{ __('Avatar preview') }}" class="size-12 rounded-lg object-cover" />
                @else
                    <x-user-avatar :user="auth()->user()" size="lg" />
                @endif

                @if (auth()->user()->avatar_path !== null)
                    <flux:button type="button" variant="danger" size="sm" wire:click="removeAvatar">
                        {{ __('Remove avatar') }}
                    </flux:button>
                @endif
            </div>

            <form wire:submit="saveAvatar" class="space-y-4">
                <flux:input
                    wire:model="avatar"
                    :label="__('Upload avatar')"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    :description="__('JPEG, PNG or WebP, up to 2 MB.')"
                />

                <flux:button type="submit" variant="primary">
                    {{ __('Save avatar') }}
                </flux:button>
            </form>
        </div>

        <flux:separator />

        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
