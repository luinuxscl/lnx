<x-layouts::app :title="__('Design gallery')" :breadcrumbs="[['label' => __('Design gallery')]]">
    <section class="flex w-full flex-col gap-8">
        <div>
            <flux:heading level="1">{{ __('Design gallery') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Examples of the interface states used across this application.') }}</flux:text>
        </div>

        {{-- Component index: each entry links to its stable anchor (#name). --}}
        <nav aria-label="{{ __('Component index') }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
            <flux:heading level="2" size="sm">{{ __('Component index') }}</flux:heading>
            <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 font-mono text-sm sm:grid-cols-3 lg:grid-cols-4">
                @foreach (['stat', 'stat-card', 'statistics-panel', 'trend-card', 'tool-card', 'placeholder-pattern', 'auth-header', 'auth-session-status', 'loading', 'empty-state', 'error-state', 'validation', 'actions-statuses', 'messaging'] as $anchor)
                    <li><a href="#{{ $anchor }}" class="text-accent-content hover:underline">{{ $anchor }}</a></li>
                @endforeach
            </ul>
        </nav>

        <section class="space-y-8" aria-label="{{ __('Shared library (lnx-ui)') }}">
            <div>
                <flux:heading level="2">{{ __('Shared library (lnx-ui)') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Reusable components from the lnx-ui package. Each one has a stable name and anchor (#name) to reference it with the AI.') }}</flux:text>
            </div>

            {{-- stat --}}
            <div id="stat" class="scroll-mt-24 space-y-3">
                <flux:heading level="3"><code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-sm dark:bg-zinc-800">x-lnx-ui::stat</code></flux:heading>

                <div class="grid grid-cols-12 gap-4">
                    <x-lnx-ui::stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Sales')" value="230k" icon="chart-pie" color="violet" />
                    <x-lnx-ui::stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Customers')" value="8.549k" icon="users" color="cyan" />
                    <x-lnx-ui::stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Products')" value="1.423k" icon="shopping-cart" color="rose" />
                    <x-lnx-ui::stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Revenue')" value="$9745" icon="currency-dollar" color="emerald" />
                </div>
            </div>

            {{-- stat-card --}}
            <div id="stat-card" class="scroll-mt-24 space-y-3">
                <flux:heading level="3"><code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-sm dark:bg-zinc-800">x-lnx-ui::stat-card</code></flux:heading>

                <div class="grid grid-cols-12 gap-4">
                    <x-lnx-ui::stat-card class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Sales')" value="230k" icon="chart-pie" color="violet" />
                    <x-lnx-ui::stat-card class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Customers')" value="8.549k" icon="users" color="cyan" />
                    <x-lnx-ui::stat-card class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Products')" value="1.423k" icon="shopping-cart" color="rose" />
                    <x-lnx-ui::stat-card class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Revenue')" value="$9745" icon="currency-dollar" color="emerald" />
                </div>
            </div>

            {{-- statistics-panel --}}
            <div id="statistics-panel" class="scroll-mt-24 space-y-3">
                <flux:heading level="3"><code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-sm dark:bg-zinc-800">x-lnx-ui::statistics-panel</code></flux:heading>

                <x-lnx-ui::statistics-panel :title="__('Statistics')" :updated-at="__('Updated 1 month ago')">
                    <x-lnx-ui::stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Sales')" value="230k" icon="chart-pie" color="violet" />
                    <x-lnx-ui::stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Customers')" value="8.549k" icon="users" color="cyan" />
                    <x-lnx-ui::stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Products')" value="1.423k" icon="shopping-cart" color="rose" />
                    <x-lnx-ui::stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Revenue')" value="$9745" icon="currency-dollar" color="emerald" />
                </x-lnx-ui::statistics-panel>

                <x-lnx-ui::statistics-panel :title="__('Statistics without update')">
                    <x-lnx-ui::stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Sales')" value="230k" icon="chart-pie" />
                </x-lnx-ui::statistics-panel>
            </div>

            {{-- trend-card --}}
            <div id="trend-card" class="scroll-mt-24 space-y-3">
                <flux:heading level="3"><code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-sm dark:bg-zinc-800">x-lnx-ui::trend-card</code></flux:heading>

                <div class="grid grid-cols-12 gap-4">
                    <x-lnx-ui::trend-card class="col-span-12 md:col-span-6 xl:col-span-4" :label="__('Product sold')" value="2.968" :change="__(':change last week', ['change' => '+3.2%'])" :series="[28, 16, 34, 42, 51, 75, 78, 80, 72, 21]" color="emerald" />
                    <x-lnx-ui::trend-card class="col-span-12 md:col-span-6 xl:col-span-4" :label="__('Total balance')" value="24.699" :change="__(':change last week', ['change' => '+0.6%'])" :series="[62, 58, 54, 49, 70, 79, 91, 57, 42, 58]" color="amber" />
                    <x-lnx-ui::trend-card class="col-span-12 md:col-span-6 xl:col-span-4" :label="__('Sales profit')" value="2.368" :change="__(':change last week', ['change' => '+0.9%'])" :series="[31, 78, 46, 50, 55, 29, 23, 18, 15, 22]" color="rose" />
                </div>
            </div>

            {{-- tool-card --}}
            <div id="tool-card" class="scroll-mt-24 space-y-3">
                <flux:heading level="3"><code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-sm dark:bg-zinc-800">x-lnx-ui::tool-card</code></flux:heading>

                <div class="grid grid-cols-12 gap-4">
                    <x-lnx-ui::tool-card class="col-span-12 sm:col-span-6 xl:col-span-4" :title="__('Chat')" :description="__('Have interactive conversations with the AI assistant.')" icon="chat-bubble-left-right" color="violet" :href="route('chat')" />
                    <x-lnx-ui::tool-card class="col-span-12 sm:col-span-6 xl:col-span-4" :title="__('Users')" :description="__('Manage accounts, roles, and invitations.')" icon="users" color="blue" :href="route('admin.users.index')" />
                    <x-lnx-ui::tool-card class="col-span-12 sm:col-span-6 xl:col-span-4" :title="__('Repository')" :description="__('Open the project repository in a new tab.')" icon="folder-git-2" external href="https://github.com/luinuxscl/lnx" />
                </div>
            </div>

            {{-- placeholder-pattern --}}
            <div id="placeholder-pattern" class="scroll-mt-24 space-y-3">
                <flux:heading level="3"><code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-sm dark:bg-zinc-800">x-lnx-ui::placeholder-pattern</code></flux:heading>

                <div class="size-32 rounded-lg border border-zinc-200 text-zinc-300 dark:border-zinc-800 dark:text-zinc-700">
                    <x-lnx-ui::placeholder-pattern class="size-full [&_path]:fill-none [&_path]:stroke-current" />
                </div>
            </div>

            {{-- auth-header --}}
            <div id="auth-header" class="scroll-mt-24 space-y-3">
                <flux:heading level="3"><code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-sm dark:bg-zinc-800">x-lnx-ui::auth-header</code></flux:heading>

                <flux:card>
                    <x-lnx-ui::auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />
                </flux:card>
            </div>

            {{-- auth-session-status --}}
            <div id="auth-session-status" class="scroll-mt-24 space-y-3">
                <flux:heading level="3"><code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-sm dark:bg-zinc-800">x-lnx-ui::auth-session-status</code></flux:heading>

                <flux:card>
                    <x-lnx-ui::auth-session-status :status="__('Your password has been reset.')" />
                </flux:card>
            </div>
        </section>

        <section class="space-y-4" aria-label="{{ __('Interface states') }}">
            <flux:heading level="2">{{ __('Interface states') }}</flux:heading>

            <div class="grid gap-6 lg:grid-cols-2">
                <flux:card id="loading" class="scroll-mt-24 space-y-4">
                    <flux:heading level="3">{{ __('Loading') }}</flux:heading>
                    <flux:skeleton class="h-5 w-2/3" />
                    <flux:skeleton class="h-5 w-full" />
                    <flux:skeleton class="h-9 w-28" />
                </flux:card>

                <flux:card id="empty-state" class="scroll-mt-24 space-y-4">
                    <flux:heading level="3">{{ __('Empty state') }}</flux:heading>
                    <flux:text>{{ __('No invitations found.') }}</flux:text>
                    <flux:button :href="route('admin.users.create')" wire:navigate>{{ __('Invite user') }}</flux:button>
                </flux:card>

                <flux:card id="error-state" class="scroll-mt-24 space-y-4">
                    <flux:heading level="3">{{ __('Error state') }}</flux:heading>
                    <flux:callout variant="danger" icon="x-circle">
                        <flux:callout.heading>{{ __('The invitation could not be sent.') }}</flux:callout.heading>
                        <flux:callout.text>{{ __('Please try again.') }}</flux:callout.text>
                    </flux:callout>
                </flux:card>

                <flux:card id="validation" class="scroll-mt-24 space-y-4">
                    <flux:heading level="3">{{ __('Validation') }}</flux:heading>
                    <flux:field>
                        <flux:label>{{ __('Email') }}</flux:label>
                        <flux:input type="email" value="invalid-email" aria-invalid="true" />
                        <flux:error name="email" :message="__('Enter a valid email address.')" />
                    </flux:field>
                </flux:card>
            </div>
        </section>

        <section id="actions-statuses" class="scroll-mt-24" aria-label="{{ __('Actions and statuses') }}">
            <flux:card class="space-y-4">
                <flux:heading level="2">{{ __('Actions and statuses') }}</flux:heading>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:badge color="green">{{ __('Active') }}</flux:badge>
                    <flux:badge color="amber">{{ __('Suspended') }}</flux:badge>
                    <flux:button variant="primary" :href="route('admin.users.create')" wire:navigate>{{ __('Invite user') }}</flux:button>
                    <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </flux:card>
        </section>

        <section id="messaging" class="scroll-mt-24 space-y-4" aria-label="{{ __('Messaging components') }}">
            <flux:heading level="2">{{ __('Messaging components') }}</flux:heading>
            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading level="3">{{ __('Chat stream sample') }}</flux:heading>
                    <flux:button size="sm" variant="primary" :href="route('messages.index')" wire:navigate>
                        {{ __('Open Messages') }}
                    </flux:button>
                </div>
                <div class="space-y-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex flex-col items-start">
                        <div class="max-w-md rounded-2xl rounded-tl-xs border border-zinc-200 bg-white p-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                            <span class="block text-xs font-bold text-zinc-500">Felecia Rower</span>
                            {{ __('Hey, I am looking for the best admin template.') }}
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <div class="max-w-md rounded-2xl rounded-tr-xs bg-gradient-to-r from-indigo-600 to-violet-600 p-3 text-sm text-white">
                            {{ __('How can we help? We are here for you!') }}
                        </div>
                    </div>
                </div>
            </flux:card>
        </section>
    </section>
</x-layouts::app>
