<section class="w-full">
    <flux:heading level="1">{{ __('Setup checklist') }}</flux:heading>
    <flux:text class="mt-1">{{ __('Verify that the essential site configuration is in place.') }}</flux:text>

    @php
        $identity = $steps['identity'];
        $email = $steps['email'];
        $storage = $steps['storage'];
        $registration = $steps['registration'];
        $badge = fn (string $status): array => match ($status) {
            'verified' => ['color' => 'green', 'icon' => 'check-circle', 'label' => __('Verified')],
            'error' => ['color' => 'red', 'icon' => 'exclamation-triangle', 'label' => __('Error')],
            default => ['color' => 'amber', 'icon' => 'clock', 'label' => __('Pending')],
        };
    @endphp

    <div class="mt-6 w-full max-w-3xl space-y-6">
        {{-- Step 1: site identity --}}
        <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <flux:heading level="2">{{ __('Site identity') }}</flux:heading>
                @php $identityBadge = $badge($identity['status']); @endphp
                <flux:badge :color="$identityBadge['color']" :icon="$identityBadge['icon']">{{ $identityBadge['label'] }}</flux:badge>
            </div>

            <flux:text>{{ __('The site title and administration email are the minimum identity for outgoing messages and search results.') }}</flux:text>

            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Title') }}</dt>
                    <dd>{{ $identity['title'] ?: __('Not set') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Tagline') }}</dt>
                    <dd>{{ $identity['tagline'] ?: __('Not set') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Administration email') }}</dt>
                    <dd>{{ $identity['admin_email'] ?: __('Not set') }}</dd>
                </div>
            </dl>

            <flux:button :href="route('admin.settings.edit')" wire:navigate icon="cog">
                {{ __('Configure site identity') }}
            </flux:button>
        </div>

        <flux:separator />

        {{-- Step 2: test email --}}
        <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <flux:heading level="2">{{ __('Test email') }}</flux:heading>
                @php $emailBadge = $badge($email['status']); @endphp
                <flux:badge :color="$emailBadge['color']" :icon="$emailBadge['icon']">{{ $emailBadge['label'] }}</flux:badge>
            </div>

            <flux:text>{{ __('Send a test message to confirm that outgoing mail is configured. The transport accepting the message does not guarantee it reaches the inbox.') }}</flux:text>

            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Mail driver') }}</dt>
                    <dd>{{ $email['driver'] ?: __('Not set') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Sender address') }}</dt>
                    <dd>{{ $email['from'] ?: __('Not set') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Recipient') }}</dt>
                    <dd>{{ $email['recipient'] ?: __('Not set') }}</dd>
                </div>
                @if ($email['sent_at'] !== null)
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Last accepted') }}</dt>
                        <dd>{{ \Illuminate\Support\Carbon::parse($email['sent_at'])->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</dd>
                    </div>
                @endif
            </dl>

            @error('test_email')
                <flux:callout variant="danger">{{ $message }}</flux:callout>
            @enderror

            <flux:button wire:click="sendTestEmail" wire:loading.attr="disabled" icon="paper-airplane">
                {{ __('Send test email') }}
            </flux:button>
        </div>

        <flux:separator />

        {{-- Step 3: storage --}}
        <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <flux:heading level="2">{{ __('Storage') }}</flux:heading>
                @php $storageBadge = $badge($storage['status']); @endphp
                <flux:badge :color="$storageBadge['color']" :icon="$storageBadge['icon']">{{ $storageBadge['label'] }}</flux:badge>
            </div>

            <flux:text>{{ __('The public disk must be linked and writable so uploaded files and generated assets are served.') }}</flux:text>

            @if ($storage['status'] === 'error')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    @if (! $storage['linked'])
                        <flux:callout.text>{{ __('The public storage link is missing. Run "php artisan storage:link" on the server.') }}</flux:callout.text>
                    @else
                        <flux:callout.text>{{ __('The public disk failed a write, read-back or delete check. Review the filesystem permissions.') }}</flux:callout.text>
                    @endif
                </flux:callout>
            @endif

            <flux:button wire:click="recheckStorage" wire:loading.attr="disabled" icon="arrow-path">
                {{ __('Re-check storage') }}
            </flux:button>
        </div>

        <flux:separator />

        {{-- Step 4: registration mode --}}
        <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <flux:heading level="2">{{ __('Registration mode') }}</flux:heading>
                @php $registrationBadge = $badge($registration['status']); @endphp
                <flux:badge :color="$registrationBadge['color']" :icon="$registrationBadge['icon']">{{ $registrationBadge['label'] }}</flux:badge>
            </div>

            <flux:text>{{ __('Choose how visitors can create accounts: open registration or invitations only.') }}</flux:text>

            @if ($registration['configured'])
                <flux:text>
                    {{ $registration['mode'] === 'invitation_only' ? __('Invitations only') : __('Open registration') }}
                    &mdash;
                    {{ $registration['mode'] === 'invitation_only' ? __('An invitation is required to create an account.') : __('Anyone can create an account.') }}
                </flux:text>
            @else
                <flux:text>{{ __('The registration mode has not been configured yet.') }}</flux:text>
            @endif

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('admin.settings.edit')" wire:navigate icon="cog">
                    {{ __('Change registration mode') }}
                </flux:button>
                <flux:button :href="route('admin.users.invitations')" wire:navigate icon="envelope">
                    {{ __('Manage invitations') }}
                </flux:button>
            </div>
        </div>
    </div>
</section>
