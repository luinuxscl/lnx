<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            @php($navigation = app(\App\Support\Navigation\MenuResolver::class)->resolve(auth()->user()))

            <flux:sidebar.nav>
                @include('partials.navigation-sidebar', ['groups' => $navigation])
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="bottom" align="end">
                <flux:button type="button" variant="ghost" size="sm" square icon="message-circle-question-mark" :aria-label="__('Feedback and support')" :title="__('Feedback and support')" />
                <flux:menu>
                    <flux:menu.item icon="plus" x-data x-on:click="$dispatch('support-open')">{{ __('Create request') }}</flux:menu.item>
                    <flux:menu.item icon="list-bullet" :href="route('support.index', ['mine' => 1])" wire:navigate>{{ __('View my requests') }}</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            <flux:button
                variant="ghost"
                size="sm"
                square
                icon="envelope"
                :href="route('messages.index')"
                wire:navigate
                :aria-label="__('Messages')"
                :title="__('Messages')"
            />
            <livewire:notifications.bell />
            <livewire:locale-switcher />

            <flux:dropdown position="top" align="end">
                <flux:profile icon-trailing="chevron-down">
                    <x-slot:avatar>
                        <x-user-avatar :user="auth()->user()" size="sm" />
                    </x-slot:avatar>
                </flux:profile>

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <x-user-avatar :user="auth()->user()" />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <livewire:support.panel />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
