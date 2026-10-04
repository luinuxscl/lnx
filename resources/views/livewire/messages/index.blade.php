<section class="w-full space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading level="1">{{ __('Messages') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Internal messaging between users, system, and support.') }}</flux:text>
        </div>

        <flux:button wire:click="openNewModal" variant="primary" icon="plus">
            {{ __('New conversation') }}
        </flux:button>
    </div>

    {{-- Main Messenger Layout Card --}}
    <div class="grid min-h-[700px] grid-cols-12 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-800 dark:bg-zinc-900/95">
        {{-- Left Column: Sidebar (Chats / Contacts) --}}
        <div class="col-span-12 flex flex-col space-y-4 border-r border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40 lg:col-span-4">
            {{-- User Header & Search --}}
            <div class="flex items-center gap-3">
                <div class="relative shrink-0">
                    <x-user-avatar :user="auth()->user()" size="sm" />
                    <span class="absolute bottom-0 right-0 size-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900"></span>
                </div>
                <div class="flex-1">
                    <flux:input
                        wire:model.live.debounce.250ms="search"
                        placeholder="{{ $tab === 'chats' ? __('Search conversations...') : __('Search contacts...') }}"
                        icon="magnifying-glass"
                        size="sm"
                    />
                </div>
            </div>

            {{-- Tabs: Chats vs Contacts --}}
            <div class="grid grid-cols-2 rounded-xl bg-zinc-200/60 p-1 dark:bg-zinc-800/60">
                <button
                    type="button"
                    wire:click="$set('tab', 'chats')"
                    class="rounded-lg py-1.5 text-xs font-semibold transition-all {{ $tab === 'chats' ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
                >
                    {{ __('Chats') }}
                </button>
                <button
                    type="button"
                    wire:click="$set('tab', 'contacts')"
                    class="rounded-lg py-1.5 text-xs font-semibold transition-all {{ $tab === 'contacts' ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
                >
                    {{ __('Contacts') }}
                </button>
            </div>

            {{-- TAB: CHATS --}}
            @if ($tab === 'chats')
                {{-- Quick filters --}}
                <div class="flex flex-wrap gap-1 border-b border-zinc-200/80 pb-2 dark:border-zinc-800">
                    <flux:button size="xs" :variant="$filter === 'all' ? 'filled' : 'ghost'" wire:click="$set('filter', 'all')">
                        {{ __('All') }}
                    </flux:button>
                    <flux:button size="xs" :variant="$filter === 'direct' ? 'filled' : 'ghost'" wire:click="$set('filter', 'direct')">
                        {{ __('Direct') }}
                    </flux:button>
                    <flux:button size="xs" :variant="$filter === 'group' ? 'filled' : 'ghost'" wire:click="$set('filter', 'group')">
                        {{ __('Groups') }}
                    </flux:button>
                    <flux:button size="xs" :variant="$filter === 'announcement' ? 'filled' : 'ghost'" wire:click="$set('filter', 'announcement')">
                        {{ __('Announcements') }}
                    </flux:button>
                    <flux:button size="xs" :variant="$filter === 'support' ? 'filled' : 'ghost'" wire:click="$set('filter', 'support')">
                        {{ __('Support') }}
                    </flux:button>
                </div>

                {{-- Conversations List --}}
                <div class="max-h-[520px] flex-1 space-y-2 overflow-y-auto pr-1">
                    @forelse ($conversations as $conv)
                        @php
                            $unread = $conv->unreadCountFor(auth()->user());
                            $isSelected = $selectedId === $conv->id;
                            $title = $conv->titleFor(auth()->user());
                            $otherUser = $conv->otherUserFor(auth()->user());
                            $lastMsg = $conv->latestMessage;
                        @endphp
                        <div
                            wire:key="conversation-{{ $conv->id }}"
                            wire:click="selectConversation({{ $conv->id }})"
                            class="group relative cursor-pointer rounded-xl border p-2.5 transition-all {{ $isSelected ? 'border-[var(--color-accent)]/40 bg-[var(--color-accent)]/10 shadow-xs dark:bg-indigo-950/30' : 'border-transparent bg-white/80 hover:border-zinc-200 hover:bg-white dark:bg-zinc-900/60 dark:hover:border-zinc-800 dark:hover:bg-zinc-800/60' }}"
                        >
                            <div class="flex items-center gap-3">
                                <div class="relative shrink-0">
                                    @if ($otherUser)
                                        <x-user-avatar :user="$otherUser" size="sm" />
                                    @else
                                        <flux:avatar :name="$title" size="sm" />
                                    @endif
                                    <span class="absolute bottom-0 right-0 size-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900"></span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-1">
                                        <h4 class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                                            {{ $title }}
                                        </h4>
                                        @if ($conv->last_message_at)
                                            <span class="shrink-0 text-[11px] text-zinc-400">
                                                {{ $conv->last_message_at->copy()->timezone($timezone)->diffForHumans(null, true) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-0.5 flex items-center justify-between gap-2">
                                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            @if ($lastMsg)
                                                {{ Str::limit($lastMsg->body, 35) }}
                                            @else
                                                <span class="italic text-zinc-400">{{ __('No messages yet') }}</span>
                                            @endif
                                        </p>

                                        @if ($unread > 0)
                                            <flux:badge color="amber" size="sm" class="shrink-0 font-bold">{{ $unread }}</flux:badge>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-zinc-400">
                            <flux:icon.chat-bubble-left-right class="mx-auto size-8 text-zinc-300 dark:text-zinc-700" />
                            <p class="mt-2 text-xs">{{ __('No conversations found.') }}</p>
                        </div>
                    @endforelse
                </div>

            {{-- TAB: CONTACTS --}}
            @else
                <div class="max-h-[560px] flex-1 space-y-2 overflow-y-auto pr-1">
                    @forelse ($availableUsers as $contactUser)
                        <div
                            wire:key="contact-{{ $contactUser->id }}"
                            wire:click="selectContactUser({{ $contactUser->id }})"
                            class="group flex cursor-pointer items-center justify-between rounded-xl border border-transparent bg-white/80 p-2.5 transition-all hover:border-[var(--color-accent)]/30 hover:bg-[var(--color-accent)]/5 dark:bg-zinc-900/60 dark:hover:border-indigo-500/30 dark:hover:bg-indigo-950/30"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="relative shrink-0">
                                    <x-user-avatar :user="$contactUser" size="sm" />
                                    <span class="absolute bottom-0 right-0 size-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900"></span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $contactUser->name }}
                                    </h4>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $contactUser->email }}
                                    </p>
                                </div>
                            </div>

                            <flux:button size="xs" variant="ghost" icon="chat-bubble-left">
                                {{ __('Start chat') }}
                            </flux:button>
                        </div>
                    @empty
                        <div class="py-12 text-center text-zinc-400">
                            <flux:icon.users class="mx-auto size-8 text-zinc-300 dark:text-zinc-700" />
                            <p class="mt-2 text-xs">{{ __('No contacts found.') }}</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>

        {{-- Right Column: Active Chat Stage --}}
        <div class="col-span-12 flex h-full flex-col justify-between bg-white dark:bg-zinc-900 lg:col-span-8">
            @if ($selectedConversation)
                @php
                    $activeTitle = $selectedConversation->titleFor(auth()->user());
                    $activeUser = $selectedConversation->otherUserFor(auth()->user());
                @endphp

                {{-- Chat Header with Search Action --}}
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            @if ($activeUser)
                                <x-user-avatar :user="$activeUser" size="sm" />
                            @else
                                <flux:avatar :name="$activeTitle" size="sm" />
                            @endif
                            <span class="absolute bottom-0 right-0 size-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-zinc-900"></span>
                        </div>
                        <div>
                            <flux:heading level="2" class="text-sm font-semibold">
                                {{ $activeTitle }}
                            </flux:heading>
                            <div class="mt-0.5 flex items-center gap-2">
                                @if ($selectedConversation->isDirect())
                                    <span class="text-[11px] text-emerald-500 font-medium">● {{ __('Online') }}</span>
                                @elseif ($selectedConversation->isGroup())
                                    <span class="text-[11px] text-zinc-400">{{ $selectedConversation->participants->count() }} {{ __('participants') }}</span>
                                @elseif ($selectedConversation->isAnnouncement())
                                    <flux:badge size="sm" color="emerald">{{ __('Announcement') }}</flux:badge>
                                @elseif ($selectedConversation->isSupport())
                                    <flux:badge size="sm" color="amber">{{ __('Support ticket') }} #{{ $selectedConversation->support_ticket_id }}</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Header Action Icon: Only Search as requested --}}
                    <div class="flex items-center gap-1">
                        <flux:button size="sm" variant="ghost" icon="magnifying-glass" tooltip="{{ __('Search in conversation') }}" />
                    </div>
                </div>

                {{-- Messages Stream Area (Exact WhatsApp alignment & tight bounds) --}}
                <div class="flex-1 space-y-2.5 overflow-y-auto p-4 max-h-[520px]">
                    @php
                        $previousDate = null;
                    @endphp

                    @forelse ($messages as $msg)
                        @php
                            $msgDate = $msg->created_at->copy()->timezone($timezone);
                            $dateKey = $msgDate->format('Y-m-d');
                            $dateLabel = $msgDate->isToday()
                                ? __('Today')
                                : ($msgDate->isYesterday() ? __('Yesterday') : $msgDate->format('d/m/Y'));
                            $isMe = $msg->user_id === auth()->id() && $msg->isUser();
                            $timeFormatted = $msgDate->isToday()
                                ? $msgDate->format('H:i')
                                : $msgDate->format('d/m H:i');
                        @endphp

                        {{-- Date Separator Pill --}}
                        @if ($previousDate !== $dateKey)
                            <div class="my-3 flex justify-center" wire:key="date-{{ $dateKey }}">
                                <span class="rounded-lg border border-zinc-200/60 bg-zinc-100/90 px-3 py-1 text-[11px] font-medium text-zinc-500 shadow-xs dark:border-zinc-800 dark:bg-zinc-800/90 dark:text-zinc-400">
                                    {{ $dateLabel }}
                                </span>
                            </div>
                            @php $previousDate = $dateKey; @endphp
                        @endif

                        @if ($msg->isSystem())
                            <div class="my-2 flex justify-center" wire:key="msg-{{ $msg->id }}">
                                <div class="max-w-md rounded-full border border-zinc-200 bg-zinc-100 px-3.5 py-1 text-center text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-800/80 dark:text-zinc-300">
                                    <span class="font-semibold">{{ $msg->displaySenderName() }}:</span> {{ $msg->body }}
                                    <span class="ml-1 text-[10px] text-zinc-400">
                                        {{ $timeFormatted }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div
                                wire:key="msg-{{ $msg->id }}"
                                class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }}"
                            >
                                <div class="relative w-fit max-w-[75%] rounded-xl px-2.5 py-1 text-sm shadow-xs {{ $isMe ? 'rounded-tr-xs bg-[var(--color-accent)] text-white text-right' : ($msg->isAi() ? 'rounded-tl-xs border border-purple-500/30 bg-purple-50 dark:bg-purple-950/40 text-purple-900 dark:text-purple-100 text-left' : 'rounded-tl-xs border border-zinc-200 bg-zinc-100 dark:border-zinc-700/80 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-left') }}">
                                    @if ($msg->isAi())
                                        <div class="mb-0.5 flex items-center gap-1 text-[11px] font-semibold text-purple-600 dark:text-purple-300">
                                            <flux:badge size="sm" color="purple">{{ __('AI') }}</flux:badge>
                                        </div>
                                    @endif

                                    <span class="whitespace-pre-wrap leading-snug">
                                        {{ $msg->body }}
                                    </span>

                                    <span class="ml-1.5 inline-flex items-center gap-0.5 align-bottom text-[10px] select-none shrink-0 {{ $isMe ? 'text-white/80' : 'text-zinc-500 dark:text-zinc-400' }}">
                                        <span>{{ $timeFormatted }}</span>
                                        @if ($isMe)
                                            <flux:icon.check class="size-3 text-white/90" />
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="flex h-full flex-col items-center justify-center py-16 text-center text-zinc-400">
                            <flux:icon.chat-bubble-bottom-center-text class="size-10 text-zinc-300 dark:text-zinc-700" />
                            <p class="mt-2 text-xs font-medium">{{ __('No messages yet. Send the first message below.') }}</p>
                        </div>
                    @endforelse
                </div>

                {{-- Input Area --}}
                @if ($canSend)
                    <div class="border-t border-zinc-200 p-3 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/20">
                        <form wire:submit="sendMessage" class="space-y-2">
                            <div class="flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-1.5 shadow-xs transition-all focus-within:border-[var(--color-accent)] focus-within:ring-1 focus-within:ring-[var(--color-accent)] dark:border-zinc-700/80 dark:bg-zinc-800/80">
                                <input
                                    type="text"
                                    wire:model="newMessageBody"
                                    placeholder="{{ __('Type your message here...') }}"
                                    class="flex-1 bg-transparent py-1 text-sm text-zinc-900 placeholder-zinc-400 outline-none dark:text-white"
                                />

                                <flux:button type="submit" variant="primary" icon="paper-airplane" size="sm">
                                    {{ __('Send') }}
                                </flux:button>
                            </div>
                            <flux:error name="newMessageBody" />
                        </form>
                    </div>
                @else
                    <div class="border-t border-zinc-200 bg-zinc-50 p-3 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
                        {{ __('You cannot send messages in this conversation.') }}
                    </div>
                @endif

            {{-- Empty State: No conversation selected --}}
            @else
                <div class="flex h-full flex-col items-center justify-center p-8 text-center">
                    <div class="flex size-14 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.chat-bubble-left-right class="size-7 text-zinc-500 dark:text-zinc-400" />
                    </div>
                    <flux:heading level="3" class="mt-3 text-base font-bold">{{ __('No conversation selected') }}</flux:heading>
                    <flux:text class="mt-1 max-w-sm text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Select a conversation from the sidebar or click contacts to start chatting.') }}
                    </flux:text>
                    <flux:button wire:click="openNewModal" variant="primary" icon="plus" class="mt-4" size="sm">
                        {{ __('New conversation') }}
                    </flux:button>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal: New Conversation --}}
    @if ($showNewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-lg rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
                    <flux:heading level="2">{{ __('Start a new conversation') }}</flux:heading>
                    <flux:button wire:click="closeNewModal" variant="ghost" size="sm" icon="x-mark" />
                </div>

                <div class="mt-4 space-y-4">
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
                                <div class="mt-2 max-h-40 space-y-1 overflow-y-auto rounded-xl border border-zinc-200 p-2 dark:border-zinc-800">
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
                                <div class="mt-2 max-h-36 space-y-1 overflow-y-auto rounded-xl border border-zinc-200 p-2 dark:border-zinc-800">
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
