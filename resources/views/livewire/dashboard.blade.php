<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('Welcome, :name', ['name' => $name])"
        :subtitle="$isRoot ? __('Your tools are here.') : __('Your account and site shortcuts are here.')"
    />

    @if ($isRoot)
        <section aria-labelledby="dashboard-administration-heading" class="space-y-4">
            <flux:heading id="dashboard-administration-heading" level="2">{{ __('Administration') }}</flux:heading>

            <div class="grid grid-cols-12 gap-4">
                <x-lnx-ui::tool-card
                    class="col-span-12 sm:col-span-6 xl:col-span-4"
                    :title="__('Support')"
                    :description="__('Review and answer support requests from every user.')"
                    icon="inbox"
                    color="rose"
                    :href="route('support.index')"
                />
                <x-lnx-ui::tool-card
                    class="col-span-12 sm:col-span-6 xl:col-span-4"
                    :title="__('Users')"
                    :description="__('Manage accounts, roles, and invitations.')"
                    icon="users"
                    color="blue"
                    :href="route('admin.users.index')"
                />
                <x-lnx-ui::tool-card
                    class="col-span-12 sm:col-span-6 xl:col-span-4"
                    :title="__('Activity')"
                    :description="__('Audit administrative changes across the application.')"
                    icon="clock"
                    color="violet"
                    :href="route('admin.activity.index')"
                />
                <x-lnx-ui::tool-card
                    class="col-span-12 sm:col-span-6 xl:col-span-4"
                    :title="__('Settings')"
                    :description="__('Configure site identity, branding, and options.')"
                    icon="cog"
                    color="cyan"
                    :href="route('admin.settings.edit')"
                />
                <x-lnx-ui::tool-card
                    class="col-span-12 sm:col-span-6 xl:col-span-4"
                    :title="__('Checklist')"
                    :description="__('Verify that the installation is fully set up.')"
                    icon="clipboard-document-check"
                    color="amber"
                    :href="route('admin.setup.index')"
                />
                <x-lnx-ui::tool-card
                    class="col-span-12 sm:col-span-6 xl:col-span-4"
                    :title="__('AI')"
                    :description="__('Check the AI provider status and configuration.')"
                    icon="sparkles"
                    color="emerald"
                    :href="route('admin.ai.index')"
                />
            </div>
        </section>

        <section aria-labelledby="dashboard-app-tools-heading" class="space-y-4">
            <flux:heading id="dashboard-app-tools-heading" level="2">{{ __('App tools') }}</flux:heading>

            <div class="grid grid-cols-12 gap-4">
                <x-lnx-ui::tool-card
                    class="col-span-12 sm:col-span-6 xl:col-span-3"
                    :title="__('Messages')"
                    :description="__('Chat with users and follow announcements.')"
                    icon="envelope"
                    :href="route('messages.index')"
                />
                <x-lnx-ui::tool-card
                    class="col-span-12 sm:col-span-6 xl:col-span-3"
                    :title="__('Chat')"
                    :description="__('Have interactive conversations with the AI assistant.')"
                    icon="chat-bubble-left-right"
                    :href="route('chat')"
                />
                <x-lnx-ui::tool-card
                    class="col-span-12 sm:col-span-6 xl:col-span-3"
                    :title="__('RAG')"
                    :description="__('Query your own knowledge base with cited answers.')"
                    icon="book-open"
                    :href="route('knowledge.index')"
                />
                <x-lnx-ui::tool-card
                    class="col-span-12 sm:col-span-6 xl:col-span-3"
                    :title="__('Document AI')"
                    :description="__('Extract structured data from documents.')"
                    icon="document-text"
                    :href="route('documents.index')"
                />
            </div>
        </section>
    @else
        @if ($userCounts !== null)
            <section aria-labelledby="dashboard-users-heading" class="space-y-4">
                <flux:heading id="dashboard-users-heading" level="2">{{ __('Users') }}</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <flux:card class="space-y-2">
                        <x-lnx-ui::stat :label="__('Total users')" :value="\Illuminate\Support\Number::format($userCounts->total)" icon="users" color="blue" />
                        <flux:button size="sm" :href="route('admin.users.index')" wire:navigate>{{ __('View users') }}</flux:button>
                    </flux:card>
                    <flux:card class="space-y-2">
                        <x-lnx-ui::stat :label="__('Active users')" :value="\Illuminate\Support\Number::format($userCounts->active)" icon="users" color="emerald" />
                        <flux:button size="sm" :href="route('admin.users.index', ['status' => 'active'])" wire:navigate>{{ __('View active users') }}</flux:button>
                    </flux:card>
                    <flux:card class="space-y-2">
                        <x-lnx-ui::stat :label="__('Suspended users')" :value="\Illuminate\Support\Number::format($userCounts->suspended)" icon="users" color="rose" />
                        <flux:button size="sm" :href="route('admin.users.index', ['status' => 'suspended'])" wire:navigate>{{ __('View suspended users') }}</flux:button>
                    </flux:card>
                    @if ($pendingInvitations !== null)
                        <flux:card class="space-y-2">
                            <x-lnx-ui::stat :label="__('Pending invitations')" :value="\Illuminate\Support\Number::format($pendingInvitations)" icon="clock" color="amber" />
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
    @endif
</section>
