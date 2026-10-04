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

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="envelope" :href="route('messages.index')" :current="request()->routeIs('messages.*')" wire:navigate>
                    {{ __('Messages') }}
                </flux:sidebar.item>

                <flux:sidebar.group :heading="__('AI Tools')" icon="sparkles" expandable :expanded="request()->routeIs('chat*', 'knowledge.*', 'documents.*')">
                    <flux:sidebar.item icon="chat-bubble-left-right" :href="route('chat')" :current="request()->routeIs('chat*')" wire:navigate>
                        {{ __('Chat') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open" :href="route('knowledge.index')" :current="request()->routeIs('knowledge.*')" wire:navigate>
                        {{ __('RAG') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('documents.index')" :current="request()->routeIs('documents.*')" wire:navigate>
                        {{ __('Document AI') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            @if (auth()->user()->hasAnyRole([\App\Enums\Role::Root, \App\Enums\Role::Admin]))
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Administration')" icon="cog-6-tooth" expandable :expanded="request()->routeIs('admin.*') || (request()->routeIs('support.index') && request()->query('mine') !== '1')">
                        @if (auth()->user()->hasRole(\App\Enums\Role::Root))
                            <flux:sidebar.item icon="inbox" :href="route('support.index')" :current="request()->routeIs('support.index') && request()->query('mine') !== '1'" wire:navigate>{{ __('Support inbox') }}</flux:sidebar.item>
                        @endif
                        <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>

                        @if (auth()->user()->hasRole(\App\Enums\Role::Root))
                            <flux:sidebar.item icon="clock" :href="route('admin.activity.index')" :current="request()->routeIs('admin.activity.*')" wire:navigate>
                                {{ __('Activity') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="cog" :href="route('admin.settings.edit')" :current="request()->routeIs('admin.settings.*')" wire:navigate>
                                {{ __('Settings') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clipboard-document-check" :href="route('admin.setup.index')" :current="request()->routeIs('admin.setup.*')" wire:navigate>
                                {{ __('Checklist') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="sparkles" :href="route('admin.ai.index')" :current="request()->routeIs('admin.ai.*')" wire:navigate>
                                {{ __('AI') }}
                            </flux:sidebar.item>

                            <flux:sidebar.item icon="folder-git-2" href="https://github.com/luinuxscl/lnx" target="_blank">
                                {{ __('Repository') }}
                            </flux:sidebar.item>
                        @endif

                        <flux:sidebar.item icon="book-open-text">
                            {{ __('Documentation') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endif

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
