@php
    $actionLabels = [
        'improve' => __('Improve'),
        'summarize' => __('Summarize'),
        'translate' => __('Translate'),
    ];
    $languageNames = [
        'es' => __('Spanish'),
        'en' => __('English'),
    ];
@endphp

<div class="w-full">
    @if (! $ready)
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-4">
            <flux:callout.text>{{ __('The assistant is not configured. You can keep editing your text normally.') }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Source text and controls --}}
        <div class="space-y-4">
            <flux:field>
                <flux:label>{{ __('Text') }}</flux:label>
                <flux:textarea wire:model.live.debounce.500ms="text" rows="8" />
                <flux:description>
                    {{ __('The assistant only sends this text.') }}
                    <span class="tabular-nums">{{ mb_strlen($text) }}/{{ $maxInputChars }}</span>
                </flux:description>
                <flux:error name="text" />
            </flux:field>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Action') }}</flux:label>
                    <flux:select wire:model.live="action">
                        @foreach ($actions as $availableAction)
                            <option value="{{ $availableAction }}">{{ $actionLabels[$availableAction] ?? $availableAction }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="action" />
                </flux:field>

                @if ($action === 'translate')
                    <flux:field>
                        <flux:label>{{ __('Target language') }}</flux:label>
                        <flux:select wire:model.live="language">
                            <option value="">{{ __('Select a language') }}</option>
                            @foreach ($languages as $availableLanguage)
                                <option value="{{ $availableLanguage }}">{{ $languageNames[$availableLanguage] ?? strtoupper($availableLanguage) }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="language" />
                    </flux:field>
                @endif
            </div>

            <flux:button
                type="button"
                variant="primary"
                icon="sparkles"
                wire:click="generate"
                wire:loading.attr="disabled"
                wire:target="generate"
                :disabled="! $ready"
            >
                {{ __('Generate') }}
            </flux:button>

            @error('generation')
                <flux:callout variant="danger" icon="exclamation-triangle">
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror
        </div>

        {{-- Proposal --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <flux:heading level="3">{{ __('Proposal') }}</flux:heading>
                @if ($proposal !== null)
                    <flux:badge color="accent" size="sm">
                        {{ $actionLabels[$proposalMeta['action'] ?? ''] ?? '' }}
                    </flux:badge>
                @endif
            </div>

            @if ($proposal === null)
                <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center dark:border-zinc-700">
                    <flux:text>{{ __('The proposal will appear here next to the original. Nothing is saved until you accept it.') }}</flux:text>
                </div>
            @else
                <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="max-h-80 overflow-y-auto whitespace-pre-wrap break-words text-sm">{{ $proposal }}</div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:button type="button" variant="primary" icon="check" wire:click="accept">
                        {{ __('Accept') }}
                    </flux:button>
                    <flux:button type="button" variant="ghost" icon="x-mark" wire:click="discard">
                        {{ __('Discard') }}
                    </flux:button>
                </div>
            @endif
        </div>
    </div>
</div>
