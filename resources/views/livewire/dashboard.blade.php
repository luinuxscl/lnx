<section class="w-full space-y-6">
    <div>
        <flux:heading level="1">{{ __('Welcome, :name', ['name' => $name]) }}</flux:heading>
        <flux:text class="mt-1">{{ __('Your account and site shortcuts are here.') }}</flux:text>
    </div>

    @if ($userCounts !== null)
        <section aria-labelledby="dashboard-users-heading" class="space-y-4">
            <flux:heading id="dashboard-users-heading" level="2">{{ __('Users') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <flux:card class="space-y-2">
                    <x-stat :label="__('Total users')" :value="\Illuminate\Support\Number::format($userCounts->total)" icon="users" color="blue" />
                    <flux:button size="sm" :href="route('admin.users.index')" wire:navigate>{{ __('View users') }}</flux:button>
                </flux:card>
                <flux:card class="space-y-2">
                    <x-stat :label="__('Active users')" :value="\Illuminate\Support\Number::format($userCounts->active)" icon="users" color="emerald" />
                    <flux:button size="sm" :href="route('admin.users.index', ['status' => 'active'])" wire:navigate>{{ __('View active users') }}</flux:button>
                </flux:card>
                <flux:card class="space-y-2">
                    <x-stat :label="__('Suspended users')" :value="\Illuminate\Support\Number::format($userCounts->suspended)" icon="users" color="rose" />
                    <flux:button size="sm" :href="route('admin.users.index', ['status' => 'suspended'])" wire:navigate>{{ __('View suspended users') }}</flux:button>
                </flux:card>
                @if ($pendingInvitations !== null)
                    <flux:card class="space-y-2">
                        <x-stat :label="__('Pending invitations')" :value="\Illuminate\Support\Number::format($pendingInvitations)" icon="clock" color="amber" />
                        @if ($pendingInvitations === 0)
                            <flux:text>{{ __('No invitations are awaiting acceptance.') }}</flux:text>
                        @endif
                        <div class="flex flex-wrap gap-2">
                            <flux:button size="sm" :href="route('admin.users.invitations')" wire:navigate>{{ __('View invitations') }}</flux:button>
                            @if ($canCreateInvitations)
                                <flux:button size="sm" variant="primary" :href="route('admin.users.create')" wire:navigate>{{ __('Invite user') }}</flux:button>
                            @endif
                        </div>
                    </flux:card>
                @endif
            </div>
        </section>
    @endif

    @if ($setup !== null)
        <section aria-labelledby="dashboard-setup-heading">
            <flux:card class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <flux:heading id="dashboard-setup-heading" level="2">{{ __('Setup checklist') }}</flux:heading>
                    <flux:badge :color="$setup['verified'] === $setup['total'] ? 'green' : ($setup['error'] > 0 ? 'red' : 'amber')">
                        {{ __(':count of :total verified', ['count' => \Illuminate\Support\Number::format($setup['verified']), 'total' => \Illuminate\Support\Number::format($setup['total'])]) }}
                    </flux:badge>
                </div>
                @if ($setup['verified'] === $setup['total'])
                    <flux:text>{{ __('All setup checks are verified.') }}</flux:text>
                @else
                    <div class="flex flex-wrap gap-4">
                        <flux:text>{{ __('Checks pending: :count', ['count' => \Illuminate\Support\Number::format($setup['pending'])]) }}</flux:text>
                        <flux:text>{{ __('Checks with errors: :count', ['count' => \Illuminate\Support\Number::format($setup['error'])]) }}</flux:text>
                    </div>
                @endif
                <div class="flex flex-wrap gap-2">
                    <flux:button :href="route('admin.setup.index')" wire:navigate>{{ __('Open setup checklist') }}</flux:button>
                    <flux:button :href="route('admin.settings.edit')" wire:navigate>{{ __('General settings') }}</flux:button>
                </div>
            </flux:card>
        </section>
    @endif

    @if ($aiUsage !== null)
        <section aria-labelledby="dashboard-ai-heading" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <flux:heading id="dashboard-ai-heading" level="2">{{ __('AI consumption this month') }}</flux:heading>
                <flux:button size="sm" :href="route('admin.ai.usage')" wire:navigate>{{ __('View consumption') }}</flux:button>
            </div>
            @if ($aiUsage['events'] === 0)
                <flux:card>
                    <flux:text>{{ __('No AI consumption has been recorded this month.') }}</flux:text>
                </flux:card>
            @else
                <div class="grid grid-cols-12 gap-4">
                    <x-stat-card class="col-span-12 sm:col-span-6 xl:col-span-3" :label="__('Attempts')" :value="\Illuminate\Support\Number::format($aiUsage['events'])" icon="sparkles" color="violet" />
                    <x-stat-card class="col-span-12 sm:col-span-6 xl:col-span-3" :label="__('Tokens')" :value="\Illuminate\Support\Number::format($aiUsage['tokens'])" icon="chart-bar" color="cyan" />
                    <x-stat-card class="col-span-12 sm:col-span-6 xl:col-span-3" :label="__('Reported cost')" :value="\App\Support\NumberFormat::currency($aiUsage['reported_cost'], $aiUsage['currency'], 4)" icon="currency-dollar" color="emerald" />
                    <x-stat-card class="col-span-12 sm:col-span-6 xl:col-span-3" :label="__('Estimated cost')" :value="\App\Support\NumberFormat::currency($aiUsage['estimated_cost'], $aiUsage['currency'], 4)" icon="currency-dollar" color="amber" />
                </div>
                @if ($aiUsage['unknown_cost'] > 0 || $aiUsage['blocked'] > 0)
                    <flux:text class="text-xs">{{ __('Unknown cost: :unknown · Blocked: :blocked', ['unknown' => \Illuminate\Support\Number::format($aiUsage['unknown_cost']), 'blocked' => \Illuminate\Support\Number::format($aiUsage['blocked'])]) }}</flux:text>
                @endif
            @endif
        </section>
    @endif

    <section aria-labelledby="dashboard-account-heading" class="space-y-4">
        <flux:heading id="dashboard-account-heading" level="2">{{ __('Your account') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:card class="space-y-3">
                <flux:heading level="3">{{ __('Profile') }}</flux:heading>
                <flux:text>{{ __('Update your personal information.') }}</flux:text>
                <flux:button :href="route('profile.edit')" wire:navigate>{{ __('Open profile') }}</flux:button>
            </flux:card>
            <flux:card class="space-y-3">
                <flux:heading level="3">{{ __('Security') }}</flux:heading>
                <flux:text>{{ __('Manage your sign-in security.') }}</flux:text>
                <flux:button :href="route('security.edit')" wire:navigate>{{ __('Open security') }}</flux:button>
            </flux:card>
        </div>
    </section>
</section>
