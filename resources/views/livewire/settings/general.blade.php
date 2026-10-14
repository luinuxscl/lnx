<section class="w-full">
    <x-lnx-ui::page-header
        :title="__('General settings')"
        :subtitle="__('Manage the site identity, branding and advanced options.')"
        icon="cog-6-tooth"
    />

    <form wire:submit="save" class="mt-6 w-full max-w-2xl space-y-8">
        <div class="space-y-6">
            <div>
                <flux:heading level="2">{{ __('Identity') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Basic information shown across the application and in search results.') }}</flux:text>
            </div>

            <flux:input wire:model="site_title" :label="__('Title')" type="text" required autofocus />

            <flux:input wire:model="site_tagline" :label="__('Tagline')" type="text" />

            <flux:textarea wire:model="meta_description" :label="__('Meta description')" rows="2" />

            <flux:input wire:model="admin_email" :label="__('Administration email')" type="email" />

            <flux:select wire:model="locale" :label="__('Language')">
                @foreach (config('app.available_locales') as $code => $label)
                    <flux:select.option value="{{ $code }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="timezone" :label="__('Time zone')">
                @foreach ($timezones as $timezone)
                    <flux:select.option value="{{ $timezone }}">{{ $timezone }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:separator />

        <div class="space-y-6">
            <div>
                <flux:heading level="2">{{ __('Personalization') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Upload the logo and icons used across the application.') }}</flux:text>
            </div>

            <div class="space-y-3">
                <flux:input wire:model="logo" :label="__('Logo')" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" />

                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" alt="{{ __('Logo preview') }}" class="h-10 w-auto rounded" />
                @elseif ($settings->logo_path)
                    <div class="flex items-center gap-3">
                        <img src="{{ $settings->logoUrl() }}" alt="{{ __('Logo preview') }}" class="h-10 w-auto rounded" />

                        <flux:button type="button" size="sm" variant="danger" wire:click="removeFile('logo')">
                            {{ __('Remove') }}
                        </flux:button>
                    </div>
                @elseif ($settings->logoUrl())
                    <img src="{{ $settings->logoUrl() }}" alt="{{ __('Logo preview') }}" class="h-10 w-auto rounded" />
                @endif
            </div>

            <div class="space-y-3">
                <flux:input wire:model="logo_dark" :label="__('Dark mode logo')" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" />

                @if ($logo_dark)
                    <img src="{{ $logo_dark->temporaryUrl() }}" alt="{{ __('Dark mode logo preview') }}" class="h-10 w-auto rounded bg-zinc-800 p-1" />
                @elseif ($settings->logo_dark_path)
                    <div class="flex items-center gap-3">
                        <img src="{{ $settings->logoDarkUrl() }}" alt="{{ __('Dark mode logo preview') }}" class="h-10 w-auto rounded bg-zinc-800 p-1" />

                        <flux:button type="button" size="sm" variant="danger" wire:click="removeFile('logo_dark')">
                            {{ __('Remove') }}
                        </flux:button>
                    </div>
                @endif
            </div>

            <div class="space-y-3">
                <flux:input wire:model="favicon" :label="__('Favicon')" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp,image/x-icon" />

                @if ($favicon)
                    <flux:text>{{ $favicon->getClientOriginalName() }}</flux:text>
                @elseif ($settings->favicon_path)
                    <div class="flex items-center gap-3">
                        <img src="{{ $settings->faviconUrl() }}" alt="{{ __('Favicon preview') }}" class="size-8 rounded" />

                        <flux:button type="button" size="sm" variant="danger" wire:click="removeFile('favicon')">
                            {{ __('Remove') }}
                        </flux:button>
                    </div>
                @elseif ($settings->faviconUrl())
                    <img src="{{ $settings->faviconUrl() }}" alt="{{ __('Favicon preview') }}" class="size-8 rounded" />
                @endif
            </div>

            <div class="space-y-3">
                <flux:input wire:model="apple_touch_icon" :label="__('Apple touch icon')" type="file" accept="image/png,image/jpeg,image/webp" />

                @if ($apple_touch_icon)
                    <img src="{{ $apple_touch_icon->temporaryUrl() }}" alt="{{ __('Apple touch icon preview') }}" class="size-12 rounded" />
                @elseif ($settings->apple_touch_icon_path)
                    <div class="flex items-center gap-3">
                        <img src="{{ $settings->appleTouchIconUrl() }}" alt="{{ __('Apple touch icon preview') }}" class="size-12 rounded" />

                        <flux:button type="button" size="sm" variant="danger" wire:click="removeFile('apple_touch_icon')">
                            {{ __('Remove') }}
                        </flux:button>
                    </div>
                @elseif ($settings->appleTouchIconUrl())
                    <img src="{{ $settings->appleTouchIconUrl() }}" alt="{{ __('Apple touch icon preview') }}" class="size-12 rounded" />
                @endif
            </div>
        </div>

        <flux:separator />

        <div class="space-y-6">
            <div>
                <flux:heading level="2">{{ __('Advanced') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Footer, analytics and maintenance options.') }}</flux:text>
            </div>

            <flux:input wire:model="footer_text" :label="__('Footer text')" type="text" />

            <flux:input wire:model="analytics_id" :label="__('Analytics ID')" type="text" placeholder="G-XXXXXXXXXX" />

            <flux:select wire:model="registration_mode" :label="__('Registration mode')">
                <flux:select.option value="open">{{ __('Open registration') }}</flux:select.option>
                <flux:select.option value="invitation_only">{{ __('Invitations only') }}</flux:select.option>
            </flux:select>

            <flux:switch
                wire:model.live="home_enabled"
                :label="__('Home page')"
                :description="__('Show a public landing page at the home route. When disabled, visitors are sent to the sign-in page and signed-in users to the dashboard.')"
            />

            <flux:switch
                wire:model.live="maintenance_mode"
                :label="__('Maintenance mode')"
                :description="__('Temporarily block access to the application. Root users can still sign in.')"
            />

            @if ($maintenance_mode)
                <flux:callout variant="warning" icon="wrench-screwdriver">
                    <flux:callout.heading>{{ __('Maintenance mode is enabled') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Visitors will see the maintenance message below.') }}</flux:callout.text>
                </flux:callout>

                <flux:textarea wire:model="maintenance_message" :label="__('Maintenance message')" rows="3" />
            @endif
        </div>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </div>
    </form>
</section>
