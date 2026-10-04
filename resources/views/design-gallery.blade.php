<x-layouts::app :title="__('Design gallery')" :breadcrumbs="[['label' => __('Design gallery')]]">
    <section class="flex w-full flex-col gap-8">
        <div>
            <flux:heading level="1">{{ __('Design gallery') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Examples of the interface states used across this application.') }}</flux:text>
        </div>

        <section class="space-y-4" aria-label="{{ __('Statistics components') }}">
            <flux:heading level="2">{{ __('Statistics components') }}</flux:heading>

            <div class="grid grid-cols-12 gap-4">
                <x-stat-card class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Sales')" value="230k" icon="chart-pie" color="violet" />
                <x-stat-card class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Customers')" value="8.549k" icon="users" color="cyan" />
                <x-stat-card class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Products')" value="1.423k" icon="shopping-cart" color="rose" />
                <x-stat-card class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Revenue')" value="$9745" icon="currency-dollar" color="emerald" />
            </div>

            <x-statistics-panel :title="__('Statistics')" :updated-at="__('Updated 1 month ago')">
                <x-stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Sales')" value="230k" icon="chart-pie" color="violet" />
                <x-stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Customers')" value="8.549k" icon="users" color="cyan" />
                <x-stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Products')" value="1.423k" icon="shopping-cart" color="rose" />
                <x-stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Revenue')" value="$9745" icon="currency-dollar" color="emerald" />
            </x-statistics-panel>

            <x-statistics-panel :title="__('Statistics without update')">
                <x-stat class="col-span-12 md:col-span-6 xl:col-span-3" :label="__('Sales')" value="230k" icon="chart-pie" />
            </x-statistics-panel>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <flux:card class="space-y-4">
                <flux:heading level="2">{{ __('Loading') }}</flux:heading>
                <flux:skeleton class="h-5 w-2/3" />
                <flux:skeleton class="h-5 w-full" />
                <flux:skeleton class="h-9 w-28" />
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading level="2">{{ __('Empty state') }}</flux:heading>
                <flux:text>{{ __('No invitations found.') }}</flux:text>
                <flux:button :href="route('admin.users.create')" wire:navigate>{{ __('Invite user') }}</flux:button>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading level="2">{{ __('Error state') }}</flux:heading>
                <flux:callout variant="danger" icon="x-circle">
                    <flux:callout.heading>{{ __('The invitation could not be sent.') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Please try again.') }}</flux:callout.text>
                </flux:callout>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading level="2">{{ __('Validation') }}</flux:heading>
                <flux:field>
                    <flux:label>{{ __('Email') }}</flux:label>
                    <flux:input type="email" value="invalid-email" aria-invalid="true" />
                    <flux:error name="email" :message="__('Enter a valid email address.')" />
                </flux:field>
            </flux:card>
        </div>

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
</x-layouts::app>
