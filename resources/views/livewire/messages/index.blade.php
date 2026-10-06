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
                <div class="shrink-0">
                    <x-user-avatar :user="auth()->user()" size="sm" />
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
                        <button
                            type="button"
                            wire:key="conversation-{{ $conv->id }}"
                            wire:click="selectConversation({{ $conv->id }})"
                            @if ($isSelected) aria-current="true" @endif
                            class="group flex w-full cursor-pointer items-center gap-3 rounded-xl border p-2.5 text-left transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-accent)] focus-visible:ring-inset {{ $isSelected ? 'border-[var(--color-accent)]/40 bg-[var(--color-accent)]/10 shadow-xs dark:bg-indigo-950/30' : 'border-transparent bg-white/80 hover:border-zinc-200 hover:bg-white dark:bg-zinc-900/60 dark:hover:border-zinc-800 dark:hover:bg-zinc-800/60' }}"
                        >
                            <div class="shrink-0">
                                @if ($otherUser)
                                    <x-user-avatar :user="$otherUser" size="sm" />
                                @else
                                    <flux:avatar :name="$title" size="sm" />
                                @endif
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
                        </button>
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
                        <button
                            type="button"
                            wire:key="contact-{{ $contactUser->id }}"
                            wire:click="selectContactUser({{ $contactUser->id }})"
                            class="group flex w-full cursor-pointer items-center justify-between gap-3 rounded-xl border border-transparent bg-white/80 p-2.5 text-left transition-all hover:border-[var(--color-accent)]/30 hover:bg-[var(--color-accent)]/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-accent)] focus-visible:ring-inset dark:bg-zinc-900/60 dark:hover:border-indigo-500/30 dark:hover:bg-indigo-950/30"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="shrink-0">
                                    <x-user-avatar :user="$contactUser" size="sm" />
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

                            <span class="flex shrink-0 items-center gap-1 text-xs font-medium text-[var(--color-accent)] opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">
                                <flux:icon.chat-bubble-left class="size-4" />
                                {{ __('Start chat') }}
                            </span>
                        </button>
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
                <div class="border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0">
                                @if ($activeUser)
                                    <x-user-avatar :user="$activeUser" size="sm" />
                                @else
                                    <flux:avatar :name="$activeTitle" size="sm" />
                                @endif
                            </div>
                            <div>
                                <flux:heading level="2" class="text-sm font-semibold">
                                    {{ $activeTitle }}
                                </flux:heading>
                                <div class="mt-0.5 flex items-center gap-2">
                                    @if ($selectedConversation->isDirect())
                                        <span class="text-[11px] text-zinc-400">{{ __('Direct message') }}</span>
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

                        <div class="flex items-center gap-1">
                            <flux:button
                                size="sm"
                                :variant="$showMessageSearch ? 'filled' : 'ghost'"
                                icon="magnifying-glass"
                                tooltip="{{ __('Search in conversation') }}"
                                wire:click="toggleMessageSearch"
                                :aria-expanded="$showMessageSearch ? 'true' : 'false'"
                            />
                        </div>
                    </div>

                    @if ($showMessageSearch)
                        <div class="flex items-center gap-2 border-t border-zinc-100 px-5 py-2 dark:border-zinc-800/60">
                            <flux:input
                                wire:model.live.debounce.300ms="messageSearch"
                                icon="magnifying-glass"
                                size="sm"
                                autofocus
                                placeholder="{{ __('Search in conversation') }}"
                                class="flex-1"
                            />
                            <span class="shrink-0 text-[11px] text-zinc-400">{{ $messages->count() }} {{ __('results') }}</span>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="x-mark"
                                wire:click="closeMessageSearch"
                                :aria-label="__('Clear search')"
                            />
                        </div>
                    @endif
                </div>

                {{-- Messages Stream Area (WhatsApp alignment, auto-scroll and live refresh) --}}
                <div
                    class="relative flex-1 min-h-0"
                    wire:poll.5s
                    x-data="{
                        showJump: false,
                        atBottom() {
                            const el = this.$refs.stream;
                            return el.scrollHeight - el.scrollTop - el.clientHeight < 80;
                        },
                        jump() {
                            this.$nextTick(() => {
                                this.$refs.stream.scrollTop = this.$refs.stream.scrollHeight;
                            });
                        },
                        init() {
                            this.jump();
                            new MutationObserver(() => {
                                if (this.atBottom()) { this.jump(); }
                                this.showJump = !this.atBottom();
                            }).observe(this.$refs.stream, { childList: true, subtree: true });
                        },
                    }"
                >
                    <div
                        x-ref="stream"
                        @scroll.throttle.150ms="showJump = !atBottom()"
                        class="h-full overflow-y-auto p-4"
                    >
                        @php
                            $previousDate = null;
                            $previousMessage = null;
                            $senderKeyFor = function (\App\Models\InternalMessage $message): string {
                                return match (true) {
                                    $message->isSystem() => 'system',
                                    $message->isAi() => 'ai:'.($message->sender_name ?? $message->user_id),
                                    default => 'user:'.$message->user_id,
                                };
                            };
                            $sameGroupAs = function (?\App\Models\InternalMessage $a, ?\App\Models\InternalMessage $b) use ($senderKeyFor, $timezone): bool {
                                if (! $a || ! $b || $senderKeyFor($a) !== $senderKeyFor($b)) {
                                    return false;
                                }
                                $timeA = $a->created_at->copy()->timezone($timezone);
                                $timeB = $b->created_at->copy()->timezone($timezone);

                                return $timeA->isSameDay($timeB) && $timeA->diffInMinutes($timeB) < 5;
                            };
                            $showSenderNames = $selectedConversation->isGroup();
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

                                $needle = trim($messageSearch);
                                $bodyHtml = e($msg->body);
                                if ($needle !== '') {
                                    $bodyHtml = preg_replace(
                                        '/('.preg_quote(e($needle), '/').')/iu',
                                        '<mark class="rounded bg-amber-200/70 px-0.5 text-inherit dark:bg-amber-400/30">$1</mark>',
                                        $bodyHtml,
                                    );
                                }

                                $isFirstInGroup = ! $sameGroupAs($previousMessage, $msg);
                                $isLastInGroup = ! $sameGroupAs($msg, $messages->get($loop->index + 1));
                                $previousMessage = $msg;
                                $bubbleRadius = $isMe
                                    ? ($isFirstInGroup ? 'rounded-tr-xs' : 'rounded-tr-none')
                                    : ($isFirstInGroup ? 'rounded-tl-xs' : 'rounded-tl-none');
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
                                class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }} {{ $isFirstInGroup ? 'mt-2.5' : 'mt-0.5' }}"
                            >
                                @if ($showSenderNames && ! $isMe && $isFirstInGroup)
                                    <span class="mb-0.5 px-1 text-[11px] font-semibold text-zinc-500 dark:text-zinc-400">
                                        {{ $msg->displaySenderName() }}
                                    </span>
                                @endif

                                <div class="relative w-fit max-w-[75%] rounded-lg px-2.5 py-1.5 text-left text-base shadow-xs {{ $bubbleRadius }} {{ $isMe ? 'bg-[var(--color-accent)] text-white' : ($msg->isAi() ? 'border border-purple-500/30 bg-purple-50 text-purple-900 dark:bg-purple-950/40 dark:text-purple-100' : 'border border-zinc-200 bg-zinc-100 text-zinc-900 dark:border-zinc-700/80 dark:bg-zinc-800 dark:text-zinc-100') }}">
                                    @if ($msg->isAi())
                                        <div class="mb-0.5 flex items-center gap-1 text-[11px] font-semibold text-purple-600 dark:text-purple-300">
                                            <flux:badge size="sm" color="purple">{{ __('AI') }}</flux:badge>
                                        </div>
                                    @endif

                                    <span class="whitespace-pre-wrap leading-snug">{!! $bodyHtml !!}</span>

                                    @if ($isLastInGroup)
                                        <span class="ml-1.5 inline-flex shrink-0 select-none items-center gap-0.5 align-bottom text-[10px] {{ $isMe ? 'text-white/80' : 'text-zinc-500 dark:text-zinc-400' }}">
                                            <span>{{ $timeFormatted }}</span>
                                            @if ($isMe)
                                                <flux:icon.check class="size-3 text-white/90" />
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @empty
                            <div class="flex h-full flex-col items-center justify-center py-16 text-center text-zinc-400">
                                <flux:icon.chat-bubble-bottom-center-text class="size-10 text-zinc-300 dark:text-zinc-700" />
                                @if (trim($messageSearch) !== '')
                                    <p class="mt-2 text-xs font-medium">{{ __('No messages match your search.') }}</p>
                                @else
                                    <p class="mt-2 text-xs font-medium">{{ __('No messages yet. Send the first message below.') }}</p>
                                @endif
                            </div>
                        @endforelse
                    </div>

                    <flux:button
                        x-show="showJump"
                        x-cloak
                        x-transition
                        @click="jump()"
                        size="sm"
                        variant="primary"
                        icon="chevron-down"
                        :aria-label="__('Scroll to latest message')"
                        class="absolute bottom-4 right-5 rounded-full shadow-lg"
                    />
                </div>

                {{-- Input Area --}}
                @if ($canSend)
                    <div class="border-t border-zinc-200 p-3 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950/20">
                        <form wire:submit="sendMessage" class="space-y-2">
                            <div
                                class="flex items-end gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-1.5 shadow-xs transition-all focus-within:border-[var(--color-accent)] focus-within:ring-1 focus-within:ring-[var(--color-accent)] dark:border-zinc-700/80 dark:bg-zinc-800/80"
                                x-data="{
                                    resize() {
                                        const el = this.$refs.composer;
                                        el.style.height = 'auto';
                                        el.style.height = Math.min(el.scrollHeight, 160) + 'px';
                                    },
                                }"
                                x-init="resize()"
                                x-effect="if ($wire.newMessageBody === '') { $nextTick(() => resize()) }"
                            >
                                <textarea
                                    x-ref="composer"
                                    rows="1"
                                    maxlength="5000"
                                    wire:model="newMessageBody"
                                    @input="resize()"
                                    @keydown.enter="if (! $event.shiftKey && ! $event.isComposing) { $event.preventDefault(); $wire.sendMessage(); }"
                                    placeholder="{{ __('Type your message here...') }}"
                                    class="max-h-40 flex-1 resize-none bg-transparent py-1.5 text-sm text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-white"
                                ></textarea>

                                <flux:button type="submit" variant="primary" icon="paper-airplane" size="sm">
                                    {{ __('Send') }}
                                </flux:button>
                            </div>
                            <span class="block px-1 text-[11px] text-zinc-400">{{ __('Enter to send, Shift+Enter for a new line') }}</span>
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
