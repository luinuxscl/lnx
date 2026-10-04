<div class="flex flex-1 flex-col h-full max-w-7xl mx-auto w-full gap-4 max-lg:px-4 py-4">
    @if (!$isReady)
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-4">
            {{ __('AI features are not configured or available. Please verify OpenRouter configuration in admin settings.') }}
        </flux:callout>
    @endif

    @if ($errorMessage)
        <flux:callout variant="danger" icon="exclamation-circle" class="mb-4">
            {{ $errorMessage }}
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 flex-1 min-h-[600px]">
        <!-- Conversations List Sidebar -->
        <div class="lg:col-span-1 flex flex-col gap-3 border-r border-neutral-200 dark:border-neutral-800 pr-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                    {{ __('Conversations') }}
                </h2>
                <flux:button wire:click="newConversation" variant="filled" size="sm" icon="plus">
                    {{ __('New') }}
                </flux:button>
            </div>

            <div class="flex flex-col gap-1 overflow-y-auto flex-1 max-h-[650px]">
                @forelse ($conversations as $conv)
                    <div class="group flex items-center justify-between p-2.5 rounded-lg text-sm transition-colors cursor-pointer {{ $activeConversationId === $conv->id ? 'bg-neutral-100 dark:bg-neutral-800 font-medium text-neutral-900 dark:text-neutral-100' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-800/50' }}"
                         wire:click="selectConversation('{{ $conv->id }}')">
                        <div class="flex items-center gap-2 truncate pr-2">
                            <flux:icon icon="chat-bubble-left" class="size-4 shrink-0 text-neutral-400" />
                            <span class="truncate">{{ $conv->title ?: __('New conversation') }}</span>
                        </div>
                        <button type="button"
                                wire:click.stop="deleteConversation('{{ $conv->id }}')"
                                wire:confirm="{{ __('Are you sure you want to delete this conversation?') }}"
                                class="opacity-0 group-hover:opacity-100 text-neutral-400 hover:text-red-600 transition-opacity p-1"
                                title="{{ __('Delete') }}">
                            <flux:icon icon="trash" class="size-3.5" />
                        </button>
                    </div>
                @empty
                    <div class="p-4 text-center text-xs text-neutral-500">
                        {{ __('No conversations yet. Start a new chat!') }}
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Chat Main Area -->
        <div class="lg:col-span-3 flex flex-col flex-1 bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800 shadow-sm overflow-hidden">
            @if ($activeConversation)
                <!-- Chat Messages Stream -->
                <div class="flex-1 p-6 overflow-y-auto flex flex-col gap-4 max-h-[550px]" id="chat-messages">
                    @forelse ($activeConversation->messages as $message)
                        @if ($message->role === 'user')
                            <div class="flex justify-end">
                                <div class="max-w-[80%] rounded-2xl px-4 py-3 bg-[var(--color-accent)] text-[var(--color-accent-foreground)] shadow-sm text-sm">
                                    {!! nl2br(e($message->content)) !!}
                                </div>
                            </div>
                        @elseif ($message->role === 'assistant')
                            <div class="flex items-start gap-3">
                                <div class="size-8 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center text-neutral-600 dark:text-neutral-300 shrink-0">
                                    <flux:icon icon="sparkles" class="size-4" />
                                </div>
                                <div class="max-w-[85%] rounded-2xl px-4 py-3 bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 text-sm shadow-sm">
                                    @if ($message->status === 'pending')
                                        <div class="flex items-center gap-2 text-neutral-500">
                                            <flux:icon icon="arrow-path" class="size-4 animate-spin" />
                                            <span>{{ __('Thinking...') }}</span>
                                        </div>
                                    @else
                                        {!! nl2br(e($message->content)) !!}
                                    @endif
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="flex flex-col items-center justify-center flex-1 text-center py-12 text-neutral-400">
                            <flux:icon icon="chat-bubble-bottom-center-text" class="size-12 mb-3 text-neutral-300 dark:text-neutral-700" />
                            <p class="text-sm font-medium">{{ __('How can I help you today?') }}</p>
                            <p class="text-xs text-neutral-500 mt-1">{{ __('Ask questions about lnx features, settings, or user preferences.') }}</p>
                        </div>
                    @endforelse
                </div>

                <!-- Input Area -->
                <div class="p-4 border-t border-neutral-200 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50">
                    <form wire:submit.prevent="sendMessage" class="flex flex-col gap-2">
                        <div class="flex gap-2">
                            <flux:input
                                wire:model.defer="inputPrompt"
                                placeholder="{{ __('Type your message...') }}"
                                class="flex-1"
                                :disabled="$isProcessing || !$isReady"
                                autocomplete="off"
                            />
                            <flux:button type="submit" variant="primary" icon="paper-airplane" :disabled="$isProcessing || !$isReady">
                                {{ __('Send') }}
                            </flux:button>
                        </div>

                        <div class="flex items-center justify-between text-xs text-neutral-500 px-1">
                            <span>{{ __('Contextual reading tools and page assistance active.') }}</span>
                            @if ($isProcessing)
                                <span class="flex items-center gap-1 text-accent font-medium">
                                    <flux:icon icon="arrow-path" class="size-3 animate-spin" />
                                    {{ __('Processing...') }}
                                </span>
                            @endif
                        </div>
                    </form>
                </div>
            @else
                <div class="flex flex-col items-center justify-center flex-1 text-center p-8 text-neutral-400">
                    <flux:icon icon="sparkles" class="size-12 mb-3 text-neutral-300 dark:text-neutral-700" />
                    <p class="text-base font-medium text-neutral-700 dark:text-neutral-300">{{ __('No conversation selected') }}</p>
                    <p class="text-xs text-neutral-500 mt-1 mb-4">{{ __('Select an existing conversation or start a new one.') }}</p>
                    <flux:button wire:click="newConversation" variant="primary" icon="plus">
                        {{ __('Start new chat') }}
                    </flux:button>
                </div>
            @endif
        </div>
    </div>
</div>
