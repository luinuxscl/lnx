<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('Welcome, :name', ['name' => $name])"
        :subtitle="$isRoot
            ? __('Your tools are here.')
            : ($userCounts !== null
                ? __('Review the accounts and act on what needs attention.')
                : __('Your account and site shortcuts are here.'))"
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
            <x-lnx-ui::statistics-panel :title="__('Users')">
                @if ($pendingInvitations === 0)
                    <x-slot:description>{{ __('No invitations are awaiting acceptance.') }}</x-slot:description>
                @endif
                @if ($canCreateInvitations)
                    <x-slot:actions>
                        <flux:button size="sm" variant="primary" icon="user-plus" :href="route('admin.users.create')" wire:navigate>
                            {{ __('Invite user') }}
                        </flux:button>
                    </x-slot:actions>
                @endif

                <x-lnx-ui::stat-card
                    class="col-span-12 sm:col-span-6 xl:col-span-3"
                    :label="__('Total users')"
                    :value="\Illuminate\Support\Number::format($userCounts['total'])"
                    icon="users"
                    color="blue"
                    :href="route('admin.users.index')"
                />
                <x-lnx-ui::stat-card
                    class="col-span-12 sm:col-span-6 xl:col-span-3"
                    :label="__('Active users')"
                    :value="\Illuminate\Support\Number::format($userCounts['active'])"
                    icon="check-circle"
                    color="emerald"
                    :href="route('admin.users.index', ['status' => 'active'])"
                />
                <x-lnx-ui::stat-card
                    class="col-span-12 sm:col-span-6 xl:col-span-3"
                    :label="__('Suspended users')"
                    :value="\Illuminate\Support\Number::format($userCounts['suspended'])"
                    icon="user-minus"
                    color="rose"
                    :href="route('admin.users.index', ['status' => 'suspended'])"
                />
                <x-lnx-ui::stat-card
                    class="col-span-12 sm:col-span-6 xl:col-span-3"
                    :label="__('Unverified users')"
                    :value="\Illuminate\Support\Number::format($userCounts['unverified'])"
                    icon="exclamation-triangle"
                    color="amber"
                    :href="route('admin.users.index', ['verified' => 'unverified'])"
                />
                <x-lnx-ui::stat-card
                    class="col-span-12 sm:col-span-6 xl:col-span-3"
                    :label="__('Administrators')"
                    :value="\Illuminate\Support\Number::format($userCounts['admins'])"
                    icon="shield-check"
                    color="violet"
                    :href="route('admin.users.index', ['role' => 'admin'])"
                />
                <x-lnx-ui::stat-card
                    class="col-span-12 sm:col-span-6 xl:col-span-3"
                    :label="__('Signups (7 days)')"
                    :value="\Illuminate\Support\Number::format($userCounts['recent'])"
                    icon="user-plus"
                    color="cyan"
                    :href="route('admin.users.index', ['sort' => 'registered', 'dir' => 'desc'])"
                />
                @if ($pendingInvitations !== null)
                    <x-lnx-ui::stat-card
                        class="col-span-12 sm:col-span-6 xl:col-span-3"
                        :label="__('Pending invitations')"
                        :value="\Illuminate\Support\Number::format($pendingInvitations)"
                        icon="clock"
                        color="amber"
                        :href="route('admin.users.invitations')"
                    />
                @endif
            </x-lnx-ui::statistics-panel>

            <section aria-labelledby="dashboard-shortcuts-heading" class="space-y-4">
                <flux:heading id="dashboard-shortcuts-heading" level="2">{{ __('Shortcuts') }}</flux:heading>

                <div class="grid grid-cols-12 gap-4">
                    <x-lnx-ui::tool-card
                        class="col-span-12 sm:col-span-6"
                        :title="__('Users')"
                        :description="__('Manage accounts, roles, and invitations.')"
                        icon="users"
                        color="blue"
                        :href="route('admin.users.index')"
                    />
                    <x-lnx-ui::tool-card
                        class="col-span-12 sm:col-span-6"
                        :title="__('Support')"
                        :description="__('Review your own support requests.')"
                        icon="inbox"
                        color="rose"
                        :href="route('support.index')"
                    />
                </div>
            </section>

            <section aria-labelledby="dashboard-account-heading" class="space-y-4">
                <flux:heading id="dashboard-account-heading" level="2">{{ __('Your account') }}</flux:heading>

                <div class="grid grid-cols-12 gap-4">
                    <x-lnx-ui::tool-card
                        class="col-span-12 sm:col-span-6"
                        :title="__('Profile')"
                        :description="__('Update your personal information.')"
                        icon="user-circle"
                        color="violet"
                        :href="route('profile.edit')"
                    />
                    <x-lnx-ui::tool-card
                        class="col-span-12 sm:col-span-6"
                        :title="__('Security')"
                        :description="__('Manage your sign-in security.')"
                        icon="lock-closed"
                        color="emerald"
                        :href="route('security.edit')"
                    />
                </div>
            </section>
        @else
            <section aria-labelledby="dashboard-account-heading" class="space-y-4">
                <flux:heading id="dashboard-account-heading" level="2">{{ __('Your account') }}</flux:heading>

                <div class="grid grid-cols-12 gap-4">
                    <x-lnx-ui::tool-card
                        class="col-span-12 sm:col-span-6 xl:col-span-4"
                        :title="__('Profile')"
                        :description="__('Update your personal information.')"
                        icon="user-circle"
                        color="violet"
                        :href="route('profile.edit')"
                    />
                    <x-lnx-ui::tool-card
                        class="col-span-12 sm:col-span-6 xl:col-span-4"
                        :title="__('Security')"
                        :description="__('Manage your sign-in security.')"
                        icon="lock-closed"
                        color="emerald"
                        :href="route('security.edit')"
                    />
                    <x-lnx-ui::tool-card
                        class="col-span-12 sm:col-span-6 xl:col-span-4"
                        :title="__('Support')"
                        :description="__('Review your own support requests.')"
                        icon="inbox"
                        color="rose"
                        :href="route('support.index')"
                    />
                </div>
            </section>
        @endif
    @endif
</section>
