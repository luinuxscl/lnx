<section class="w-full">
    <x-lnx-ui::page-header
        :title="__('Sent emails')"
        :subtitle="__('Review the emails the application has sent to users.')"
        icon="envelope"
        color="blue"
    />

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search emails...')" icon="magnifying-glass" />
        <flux:select wire:model.live="status" :label="__('Status')">
            <option value="all">{{ __('All') }}</option>
            <option value="sent">{{ __('Sent') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
        </flux:select>
    </div>

    {{-- These are outbound messages: the mail pieces' «sender» always maps to the recipient of the send. --}}
    <div class="mt-6 grid grid-cols-12 gap-4">
        {{-- Email list --}}
        <div class="col-span-12 lg:col-span-5">
            <flux:card class="flex h-full flex-col gap-0 p-0">
                <div class="flex items-center gap-3 border-b border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:heading level="3" class="flex-1">{{ __('Sent emails') }}</flux:heading>
                    <flux:badge size="sm">{{ $emails->total() }}</flux:badge>
                </div>

                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($emails as $email)
                        @php
                            $sentAt = ($email->sent_at ?? $email->created_at)->copy()->timezone($timezone);
                        @endphp

                        <x-lnx-ui::mail-list-item
                            wire:key="email-{{ $email->id }}"
                            wire:click="selectEmail({{ $email->id }})"
                            :sender="$email->to_name ?? $email->user?->name ?? $email->to_email"
                            :email="$email->to_email"
                            :subject="$email->subject ?? __('No subject')"
                            :time="$sentAt->format('Y-m-d H:i')"
                            :badge="[
                                'label' => $email->isSent() ? __('Sent') : __('Failed'),
                                'color' => $email->isSent() ? 'emerald' : 'rose',
                            ]"
                            :selected="$selectedEmail !== null && $selectedEmail->id === $email->id"
                            class="cursor-pointer"
                        >
                            <x-slot:select></x-slot:select>
                            <x-slot:star></x-slot:star>
                        </x-lnx-ui::mail-list-item>
                    @empty
                        <div class="p-6">
                            <flux:text>{{ __('No emails found.') }}</flux:text>
                        </div>
                    @endforelse
                </div>

                @if ($emails->hasPages())
                    <div class="border-t border-zinc-200 p-3 dark:border-zinc-700">
                        {{ $emails->links() }}
                    </div>
                @endif
            </flux:card>
        </div>

        {{-- Detail --}}
        <div class="col-span-12 lg:col-span-7">
            @if ($selectedEmail)
                <x-lnx-ui::mail-viewer
                    wire:key="detail-{{ $selectedEmail->id }}"
                    :sender="$selectedEmail->to_name ?? $selectedEmail->user?->name ?? $selectedEmail->to_email"
                    :email="$selectedEmail->to_email"
                    :subject="$selectedEmail->subject ?? __('No subject')"
                    :date="($selectedEmail->sent_at ?? $selectedEmail->created_at)->copy()->timezone($timezone)->format('Y-m-d H:i')"
                    class="h-full"
                >
                    <x-slot:actions>
                        <flux:badge :color="$selectedEmail->isSent() ? 'emerald' : 'rose'">
                            {{ $selectedEmail->isSent() ? __('Sent') : __('Failed') }}
                        </flux:badge>
                    </x-slot:actions>

                    <dl>
                        <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-zinc-100">
                            {{ $selectedEmail->isSent() ? __('Sent') : __('Failed') }}
                        </dd>
                    </dl>

                    @if (! $selectedEmail->isSent())
                        <flux:callout variant="danger" icon="exclamation-triangle">
                            <flux:callout.heading>{{ __('Delivery failed') }}</flux:callout.heading>
                            <flux:callout.text>
                                {{ $selectedEmail->error ?: __('The transport rejected the message and no reason was recorded.') }}
                            </flux:callout.text>
                        </flux:callout>
                    @endif
                </x-lnx-ui::mail-viewer>
            @else
                <flux:card class="flex h-full min-h-64 items-center justify-center">
                    <div class="text-center">
                        <flux:icon icon="envelope-open" class="mx-auto size-8 text-zinc-400" />
                        <flux:text class="mt-2">{{ __('Select an email to review it.') }}</flux:text>
                    </div>
                </flux:card>
            @endif
        </div>
    </div>
</section>
