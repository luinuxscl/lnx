<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading level="1">{{ __('Messages') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Internal messaging between users, system, and support.') }}</flux:text>
        </div>

        <flux:button wire:click="openNewModal" variant="primary" icon="plus">
            {{ __('New conversation') }}
        </flux:button>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        {{-- Left column: Conversations list --}}
        <div class="space-y-4 lg:col-span-4">
            <div class="flex flex-wrap gap-1 border-b border-zinc-200 pb-2 dark:border-zinc-700">
                <flux:button size="sm" :variant="$filter === 'all' ? 'filled' : 'ghost'" wire:click="$set('filter', 'all')">
                    {{ __('All') }}
                </flux:button>
                <flux:button size="sm" :variant="$filter === 'direct' ? 'filled' : 'ghost'" wire:click="$set('filter', 'direct')">
                    {{ __('Direct') }}
                </flux:button>
                <flux:button size="sm" :variant="$filter === 'group' ? 'filled' : 'ghost'" wire:click="$set('filter', 'group')">
                    {{ __('Groups') }}
                </flux:button>
                <flux:button size="sm" :variant="$filter === 'announcement' ? 'filled' : 'ghost'" wire:click="$set('filter', 'announcement')">
                    {{ __('Announcements') }}
                </flux:button>
                <flux:button size="sm" :variant="$filter === 'support' ? 'filled' : 'ghost'" wire:click="$set('filter', 'support')">
                    {{ __('Support') }}
                </flux:button>
            </div>

            <div>
                <flux:input wire:model.live.debounce.250ms="search" placeholder="{{ __('Search conversations...') }}" icon="magnifying-glass" />
            </div>

            <div class="max-h-[600px] space-y-2 overflow-y-auto">
                @forelse ($conversations as $conv)
                    @php
                        $unread = $conv->unreadCountFor(auth()->user());
                        $isSelected = $selectedId === $conv->id;
                    @endphp
                    <div
                        wire:key="conversation-{{ $conv->id }}"
                        wire:click="selectConversation({{ $conv->id }})"
                        class="cursor-pointer rounded-lg border p-3 transition-colors {{ $isSelected ? 'border-[var(--color-accent)] bg-zinc-50 dark:bg-zinc-800' : 'border-zinc-200 bg-white hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800/60' }}"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <flux:heading level="3" class="truncate font-medium">
                                        {{ $conv->titleFor(auth()->user()) }}
                                    </flux:heading>
                                    @if ($unread > 0)
                                        <flux:badge color="amber" size="sm">{{ $unread }}</flux:badge>
                                    @endif
                                </div>

                                <div class="mt-1 flex items-center gap-2">
                                    @if ($conv->isDirect())
                                        <flux:badge size="sm" color="zinc">{{ __('Direct') }}</flux:badge>
                                    @elseif ($conv->isGroup())
                                        <flux:badge size="sm" color="blue">{{ __('Group') }}</flux:badge>
                                    @elseif ($conv->isAnnouncement())
                                        <flux:badge size="sm" color="emerald">{{ __('Announcement') }}</flux:badge>
                                    @elseif ($conv->isSupport())
                                        <flux:badge size="sm" color="amber">{{ __('Support') }}</flux:badge>
                                    @endif

                                    @if ($conv->last_message_at)
                                        <flux:text class="text-xs text-zinc-500">
                                            {{ $conv->last_message_at->copy()->timezone($timezone)->format('d/m H:i') }}
                                        </flux:text>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <flux:card>
                        <flux:text>{{ __('No conversations found.') }}</flux:text>
                    </flux:card>
                @endforelse
            </div>
        </div>

        {{-- Right column: Active conversation pane --}}
        <div class="lg:col-span-8">
            @if ($selectedConversation)
                <flux:card class="flex h-[700px] flex-col justify-between space-y-4">
                    {{-- Conversation Header --}}
                    <div class="flex flex-wrap items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700">
                        <div>
                            <flux:heading level="2">{{ $selectedConversation->titleFor(auth()->user()) }}</flux:heading>
                            <div class="mt-1 flex items-center gap-2">
                                @if ($selectedConversation->isDirect())
                                    <flux:badge color="zinc">{{ __('Direct message') }}</flux:badge>
                                @elseif ($selectedConversation->isGroup())
                                    <flux:badge color="blue">{{ __('Group chat') }} ({{ $selectedConversation->participants->count() }} {{ __('participants') }})</flux:badge>
                                @elseif ($selectedConversation->isAnnouncement())
                                    <flux:badge color="emerald">{{ __('Broadcast announcement') }}</flux:badge>
                                @elseif ($selectedConversation->isSupport())
                                    <flux:badge color="amber">{{ __('Support ticket') }} #{{ $selectedConversation->support_ticket_id }}</flux:badge>
                                    <a href="{{ route('support.show', $selectedConversation->support_ticket_id) }}" class="text-xs text-[var(--color-accent)] underline" wire:navigate>
                                        {{ __('View ticket') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Messages Stream --}}
                    <div class="flex-1 space-y-3 overflow-y-auto pr-2">
                        @forelse ($messages as $msg)
                            @php
                                $isMe = $msg->user_id === auth()->id() && $msg->isUser();
                            @endphp

                            @if ($msg->isSystem())
                                <div class="my-3 flex justify-center" wire:key="msg-{{ $msg->id }}">
                                    <div class="max-w-md rounded-md border border-zinc-200 bg-zinc-100 px-3 py-1.5 text-center text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        <div class="font-medium">{{ $msg->displaySenderName() }}</div>
                                        <div>{{ $msg->body }}</div>
                                        <div class="mt-0.5 text-[10px] text-zinc-400">
                                            {{ $msg->created_at->copy()->timezone($timezone)->format('H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div
                                    wire:key="msg-{{ $msg->id }}"
                                    class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}"
                                >
                                    <div class="max-w-lg rounded-lg border p-3 {{ $isMe ? 'border-[var(--color-accent)] bg-[var(--color-accent)]/10 text-zinc-900 dark:text-zinc-100' : ($msg->isAi() ? 'border-purple-300 bg-purple-50 dark:border-purple-800 dark:bg-purple-950/40' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800') }}">
                                        <div class="flex items-center gap-2">
                                            <flux:heading level="4" class="text-xs font-semibold">
                                                {{ $msg->displaySenderName() }}
                                            </flux:heading>

                                            @if ($msg->isAi())
                                                <flux:badge size="sm" color="purple">{{ __('AI') }}</flux:badge>
                                            @endif

                                            @if ($msg->support_message_id)
                                                <flux:badge size="sm" color="amber">{{ __('Official support reply') }}</flux:badge>
                                            @endif

                                            <flux:text class="text-[10px] text-zinc-400">
                                                {{ $msg->created_at->copy()->timezone($timezone)->format('H:i') }}
                                            </flux:text>
                                        </div>

                                        <flux:text class="mt-1 whitespace-pre-wrap text-sm">
                                            {{ $msg->body }}
                                        </flux:text>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="py-8 text-center text-zinc-500">
                                {{ __('No messages yet. Send the first message below.') }}
                            </div>
                        @endforelse
                    </div>

                    {{-- Send Area --}}
                    @if ($canSend)
                        <form wire:submit="sendMessage" class="border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            <div class="space-y-2">
                                <flux:textarea
                                    wire:model="newMessageBody"
                                    placeholder="{{ __('Write a message...') }}"
                                    rows="2"
                                />
                                <flux:error name="newMessageBody" />

                                <div class="flex items-center justify-between">
                                    <flux:text class="text-xs text-zinc-400">
                                        @if ($selectedConversation->isSupport())
                                            {{ __('Your message will be recorded in the official support ticket.') }}
                                        @endif
                                    </flux:text>
                                    <flux:button type="submit" variant="primary" icon="paper-airplane">
                                        {{ __('Send') }}
                                    </flux:button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="rounded border border-zinc-200 bg-zinc-50 p-3 text-center text-xs text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                            @if ($selectedConversation->isAnnouncement())
                                {{ __('This announcement is read-only. Replies are not permitted.') }}
                            @elseif ($selectedConversation->isSupport())
                                {{ __('This support ticket is closed.') }}
                            @else
                                {{ __('You cannot send messages in this conversation.') }}
                            @endif
                        </div>
                    @endif
                </flux:card>
            @else
                <flux:card class="flex h-[700px] flex-col items-center justify-center text-center">
                    <flux:icon.chat-bubble-left-right class="size-12 text-zinc-400" />
                    <flux:heading level="3" class="mt-3">{{ __('No conversation selected') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Select a conversation from the list or start a new one.') }}</flux:text>
                    <flux:button wire:click="openNewModal" variant="primary" class="mt-4">
                        {{ __('New conversation') }}
                    </flux:button>
                </flux:card>
            @endif
        </div>
    </div>

    {{-- Modal: New Conversation --}}
    @if ($showNewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700">
                    <flux:heading level="2">{{ __('Start a new conversation') }}</flux:heading>
                    <flux:button wire:click="closeNewModal" variant="ghost" size="sm" icon="x-mark" />
                </div>

                <div class="mt-4 space-y-4">
                    {{-- Conversation Type Selector (only for root/admin) --}}
                    @if ($canManageGroups)
                        <div class="flex gap-2">
                            <flux:button
                                size="sm"
                                :variant="$newType === 'direct' ? 'filled' : 'ghost'"
                                wire:click="$set('newType', 'direct')"
                            >
                                {{ __('Direct message') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                :variant="$newType === 'group' ? 'filled' : 'ghost'"
                                wire:click="$set('newType', 'group')"
                            >
                                {{ __('Group chat') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                :variant="$newType === 'announcement' ? 'filled' : 'ghost'"
                                wire:click="$set('newType', 'announcement')"
                            >
                                {{ __('Announcement') }}
                            </flux:button>
                        </div>
                    @endif

                    @if ($newType === 'direct')
                        <div class="space-y-3">
                            <flux:select wire:model="directRecipientId" :label="__('Select recipient')">
                                <option value="">{{ __('Choose a user...') }}</option>
                                @foreach ($availableUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="directRecipientId" />

                            <div class="flex justify-end gap-2 pt-2">
                                <flux:button wire:click="closeNewModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                                <flux:button wire:click="startDirectConversation" variant="primary">{{ __('Start chat') }}</flux:button>
                            </div>
                        </div>
                    @elseif ($newType === 'group')
                        <div class="space-y-3">
                            <flux:input wire:model="groupTitle" :label="__('Group title')" placeholder="{{ __('e.g. Project Team') }}" />
                            <flux:error name="groupTitle" />

                            <div>
                                <flux:label>{{ __('Select participants') }}</flux:label>
                                <div class="mt-2 max-h-40 space-y-1 overflow-y-auto rounded border border-zinc-200 p-2 dark:border-zinc-700">
                                    @foreach ($availableUsers as $u)
                                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                            <input type="checkbox" wire:model="selectedUserIds" value="{{ $u->id }}" class="rounded">
                                            <span>{{ $u->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <flux:error name="selectedUserIds" />
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <flux:button wire:click="closeNewModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                                <flux:button wire:click="createGroup" variant="primary">{{ __('Create group') }}</flux:button>
                            </div>
                        </div>
                    @elseif ($newType === 'announcement')
                        <div class="space-y-3">
                            <flux:input wire:model="announcementTitle" :label="__('Announcement title')" placeholder="{{ __('e.g. System Maintenance Notice') }}" />
                            <flux:error name="announcementTitle" />

                            <flux:textarea wire:model="announcementBody" :label="__('Announcement message')" rows="3" />
                            <flux:error name="announcementBody" />

                            <div>
                                <flux:label>{{ __('Select recipients') }}</flux:label>
                                <div class="mt-2 max-h-36 space-y-1 overflow-y-auto rounded border border-zinc-200 p-2 dark:border-zinc-700">
                                    @foreach ($availableUsers as $u)
                                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                            <input type="checkbox" wire:model="selectedUserIds" value="{{ $u->id }}" class="rounded">
                                            <span>{{ $u->name }} ({{ $u->email }})</span>
                                        </label>
                                    @endforeach
                                </div>
                                <flux:error name="selectedUserIds" />
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <flux:button wire:click="closeNewModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                                <flux:button wire:click="createAnnouncement" variant="primary">{{ __('Broadcast announcement') }}</flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</section>
