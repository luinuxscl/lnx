<div class="w-full">
    <div class="grid grid-cols-12 gap-4">

        {{-- Navigation --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="flex flex-col gap-3">
                <flux:button variant="primary" icon="pencil-square" class="w-full">
                    {{ __('Compose mail') }}
                </flux:button>

                <div class="flex items-center gap-3 rounded-lg bg-accent/10 p-3">
                    <flux:avatar name="J'son Taylor" circle />

                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">J'son Taylor</p>
                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">json.taylor1992@gmail.com</p>
                    </div>
                </div>

                <flux:navlist aria-label="{{ __('Mail') }}">
                    @foreach ($folders as $folderItem)
                        <flux:navlist.item
                            :icon="$folderItem['icon']"
                            :badge="$folderItem['badge']"
                            :current="$folderItem['key'] === $activeFolder"
                            wire:click="selectFolder('{{ $folderItem['key'] }}')"
                        >{{ $folderItem['label'] }}</flux:navlist.item>
                    @endforeach
                </flux:navlist>

                <div class="mt-2 space-y-0.5">
                    <flux:text class="block px-3 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ __('Labels') }}
                    </flux:text>

                    @foreach ($labels as $label)
                        @php
                            $dot = match ($label['color']) {
                                'rose' => 'bg-rose-500',
                                'emerald' => 'bg-emerald-500',
                                'blue' => 'bg-blue-500',
                                'violet' => 'bg-violet-500',
                                'amber' => 'bg-amber-500',
                                default => 'bg-accent',
                            };
                        @endphp

                        <button
                            type="button"
                            class="flex h-8 w-full items-center gap-3 rounded-lg px-3 text-sm text-zinc-500 transition hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-white/80 dark:hover:bg-white/[7%] dark:hover:text-white"
                        >
                            <span @class(['size-2 shrink-0 rounded-full', $dot]) aria-hidden="true"></span>
                            <span class="flex-1 text-start">{{ $label['label'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-2">
                    <x-lnx-ui::presence-list :title="__('Online users')" :users="$people" />
                </div>
            </div>
        </div>

        {{-- Message list --}}
        <div class="col-span-12 lg:col-span-4">
            <flux:card class="flex h-full flex-col gap-0 p-0">
                <div class="flex items-center gap-3 border-b border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:checkbox
                        :checked="count($messages) > 0 && count($checked) === count($messages)"
                        wire:click="toggleAll"
                        :aria-label="__('Select all')"
                    />

                    <flux:heading level="3" class="flex-1">{{ __('All mails') }}</flux:heading>

                    <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" :aria-label="__('More actions')" />
                </div>

                <div class="border-b border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:input
                        icon="magnifying-glass"
                        :placeholder="__('Search email')"
                        wire:model.live.debounce.300ms="search"
                    />
                </div>

                <div class="min-h-0 flex-1 divide-y divide-zinc-200 overflow-y-auto dark:divide-zinc-700">
                    @forelse ($messages as $message)
                        <x-lnx-ui::mail-list-item
                            wire:key="message-{{ $message['id'] }}"
                            wire:click="selectMessage({{ $message['id'] }})"
                            :sender="$message['sender']"
                            :email="$message['email']"
                            :subject="$message['subject']"
                            :preview="$message['preview']"
                            :time="$message['time']"
                            :badge="$message['badge']"
                            :unread="$message['unread']"
                            :starred="$this->isStarred($message['id'])"
                            :selected="$message['id'] === $selectedId"
                            class="cursor-pointer"
                        >
                            <x-slot:select>
                                <flux:checkbox
                                    :checked="$this->isChecked($message['id'])"
                                    wire:click.stop="toggleCheck({{ $message['id'] }})"
                                    :aria-label="__('Select all')"
                                />
                            </x-slot:select>

                            <x-slot:star>
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="star"
                                    :icon:variant="$this->isStarred($message['id']) ? 'solid' : 'outline'"
                                    :class="$this->isStarred($message['id']) ? 'text-amber-500' : ''"
                                    wire:click.stop="toggleStar({{ $message['id'] }})"
                                    :aria-label="__('Star')"
                                />
                            </x-slot:star>
                        </x-lnx-ui::mail-list-item>
                    @empty
                        <div class="p-6">
                            <flux:text>{{ __('No messages found.') }}</flux:text>
                        </div>
                    @endforelse
                </div>
            </flux:card>
        </div>

        {{-- Detail --}}
        <div class="col-span-12 lg:col-span-5">
            @if ($selected)
                <x-lnx-ui::mail-viewer
                    :sender="$selected['sender']"
                    :email="$selected['email']"
                    :subject="$selected['subject']"
                    :date="$selected['date'] ?? null"
                    class="h-full"
                >
                    <x-slot:actions>
                        <flux:button size="sm" variant="ghost" icon="star" wire:click="toggleStar({{ $selected['id'] }})" :aria-label="__('Star')" />
                        <flux:button size="sm" variant="ghost" icon="folder" :aria-label="__('Move to folder')" />
                        <flux:button size="sm" variant="ghost" icon="information-circle" :aria-label="__('Details')" />
                        <flux:button size="sm" variant="ghost" icon="trash" :aria-label="__('Delete')" />
                        <flux:button size="sm" variant="ghost" icon="arrow-uturn-left" :aria-label="__('Reply')" />
                    </x-slot:actions>

                    <x-slot:attachments>
                        @if (! empty($selected['attachments']))
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <flux:heading level="3">{{ __('Attachments') }}</flux:heading>

                                <flux:button size="sm" variant="filled" icon="arrow-down-tray">
                                    {{ __('Download all') }}
                                </flux:button>
                            </div>

                            <div class="grid grid-cols-12 gap-3">
                                @foreach ($selected['attachments'] as $attachment)
                                    <div class="col-span-12 sm:col-span-6">
                                        <x-lnx-ui::attachment-card
                                            :name="$attachment['name']"
                                            :size="$attachment['size']"
                                            :icon="$attachment['icon']"
                                            :color="$attachment['color']"
                                            href="#"
                                        />
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-slot:attachments>

                    <x-slot:reply>
                        <flux:heading level="3">{{ __('Reply') }}</flux:heading>

                        <div class="space-y-2">
                            <flux:textarea rows="4" :placeholder="__('Write your reply…')" wire:model="reply" />

                            <flux:error name="reply" />

                            <div class="flex justify-end">
                                <flux:button size="sm" variant="primary" icon="paper-airplane" wire:click="sendReply">
                                    {{ __('Send reply') }}
                                </flux:button>
                            </div>
                        </div>
                    </x-slot:reply>

                    <x-slot:footer>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <flux:button size="sm" variant="ghost" icon="paper-clip" :aria-label="__('Attach file')" />

                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="filled" icon="arrow-uturn-right" color="blue">
                                    {{ __('Forward') }}
                                </flux:button>
                                <flux:button size="sm" variant="primary" icon="arrow-uturn-left" color="rose">
                                    {{ __('Reply') }}
                                </flux:button>
                            </div>
                        </div>
                    </x-slot:footer>

                    @foreach (($selected['body'] ?? ['Lorem ipsum dolor sit amet consectetur adipisicing elit.']) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach

                    <p>
                        {{ __('Regards') }},<br>
                        {{ $selected['sender'] }}
                    </p>
                </x-lnx-ui::mail-viewer>
            @else
                <flux:card class="flex h-full min-h-64 items-center justify-center">
                    <flux:text>{{ __('Select a message to read it.') }}</flux:text>
                </flux:card>
            @endif
        </div>

        {{-- Presence rail --}}
        <div class="col-span-12 hidden xl:col-span-1 xl:block">
            <div class="flex flex-col items-center gap-3">
                <flux:button size="sm" variant="filled" icon="plus" :aria-label="__('New message')" />

                @foreach ($people as $person)
                    <flux:avatar :name="$person['name']" circle size="sm" />
                @endforeach
            </div>
        </div>
    </div>
</div>
