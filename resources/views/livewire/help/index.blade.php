<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('Help center')"
        :subtitle="__('Find guides and answers, or contact support when you need a hand.')"
        icon="lifebuoy"
        color="cyan"
    >
        <x-slot:actions>
            <flux:button icon="lifebuoy" variant="primary" x-data x-on:click="$dispatch('support-open')">
                {{ __('Contact support') }}
            </flux:button>
        </x-slot:actions>
    </x-lnx-ui::page-header>

    <nav class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700" aria-label="{{ __('Help sections') }}">
        @foreach ([
            'guides' => ['label' => __('Guides'), 'icon' => 'book-open', 'count' => $articleCount],
            'faq' => ['label' => __('FAQ'), 'icon' => 'question-mark-circle', 'count' => $faqCount],
            'support' => ['label' => __('Support'), 'icon' => 'lifebuoy', 'count' => null],
        ] as $value => $tab)
            <a
                href="{{ route('help.index', ['tab' => $value]) }}"
                wire:navigate
                @class([
                    'inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition',
                    'border-accent text-zinc-900 dark:text-white' => $this->tab === $value,
                    'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $this->tab !== $value,
                ])
                @if ($this->tab === $value) aria-current="page" @endif
            >
                <flux:icon :icon="$tab['icon']" variant="mini" class="size-4" aria-hidden="true" />
                {{ $tab['label'] }}
                @if ($tab['count'] !== null)
                    <flux:badge size="sm">{{ $tab['count'] }}</flux:badge>
                @endif
            </a>
        @endforeach
    </nav>

    @if ($selected !== null)
        <article class="space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <flux:button
                    icon="arrow-left"
                    size="sm"
                    :href="route('help.index', ['tab' => $this->tab, 'search' => $this->search])"
                    wire:navigate
                >
                    {{ __('Back to help center') }}
                </flux:button>
                <flux:badge :color="$selected['type'] === 'faq' ? 'amber' : 'cyan'">
                    {{ $selected['type'] === 'faq' ? __('FAQ') : __('Guide') }}
                </flux:badge>
                <flux:badge>{{ __($selected['group']) }}</flux:badge>
            </div>

            <header class="space-y-2">
                <flux:heading level="1" size="xl">{{ __($selected['title']) }}</flux:heading>
                <flux:text>{{ __($selected['summary']) }}</flux:text>
                <flux:text class="text-xs">
                    {{ __('From :source', ['source' => $selected['source']]) }}
                    @if ($selected['roles'] !== [])
                        · {{ __('Restricted to :roles', ['roles' => implode(', ', array_map(fn (string $role): string => __(ucfirst($role)), $selected['roles']))]) }}
                    @endif
                </flux:text>
            </header>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 text-sm leading-6 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 [&_a]:font-medium [&_a]:text-accent-content [&_a]:underline [&_a]:underline-offset-2 [&_blockquote]:border-s-4 [&_blockquote]:border-zinc-300 [&_blockquote]:ps-4 [&_blockquote]:italic dark:[&_blockquote]:border-zinc-600 [&_code]:rounded [&_code]:bg-zinc-100 [&_code]:px-1 [&_code]:py-0.5 [&_code]:text-xs dark:[&_code]:bg-zinc-800 [&_h2]:mt-6 [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-zinc-900 dark:[&_h2]:text-white [&_h3]:mt-4 [&_h3]:font-semibold [&_h3]:text-zinc-900 dark:[&_h3]:text-white [&_li]:my-1 [&_ol]:my-3 [&_ol]:list-decimal [&_ol]:ps-5 [&_p]:my-3 [&_pre]:my-3 [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-zinc-900 [&_pre]:p-4 [&_pre]:text-xs [&_pre]:text-zinc-100 [&_ul]:my-3 [&_ul]:list-disc [&_ul]:ps-5">
                {!! $selectedHtml !!}
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if ($selectedRoute !== null)
                    <flux:button icon="arrow-top-right-on-square" :href="$selectedRoute" wire:navigate>
                        {{ __('Go to the feature') }}
                    </flux:button>
                @endif
                <flux:button icon="arrow-left" :href="route('help.index', ['tab' => $this->tab])" wire:navigate>
                    {{ __('See more :section', ['section' => $this->tab === 'faq' ? __('FAQ') : __('guides')]) }}
                </flux:button>
            </div>

            <flux:callout icon="lifebuoy">
                <flux:callout.heading>{{ __('Did this not answer your question?') }}</flux:callout.heading>
                <flux:callout.text>{{ __('Open a support request and we will follow up with you.') }}</flux:callout.text>
                <x-slot:actions>
                    <flux:button size="sm" variant="primary" x-data x-on:click="$dispatch('support-open', { help_topic: @js($selected['slug']) })">
                        {{ __('Contact support') }}
                    </flux:button>
                </x-slot:actions>
            </flux:callout>
        </article>

    @elseif ($this->tab === 'support')
        <div class="grid gap-4 lg:grid-cols-2">
            <flux:card class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-cyan-100 text-cyan-600 dark:bg-cyan-500/15 dark:text-cyan-400" aria-hidden="true">
                        <flux:icon icon="plus" class="size-5" />
                    </span>
                    <flux:heading level="2">{{ __('Create a support request') }}</flux:heading>
                </div>
                <flux:text>{{ __('Tell us what happened, attach a screenshot and we will help you from the support panel.') }}</flux:text>
                <flux:button variant="primary" icon="plus" x-data x-on:click="$dispatch('support-open')">
                    {{ __('Create request') }}
                </flux:button>
            </flux:card>

            <flux:card class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300" aria-hidden="true">
                        <flux:icon icon="list-bullet" class="size-5" />
                    </span>
                    <flux:heading level="2">{{ __('My requests') }}</flux:heading>
                </div>
                <flux:text>{{ __('Follow the status of the requests you have already sent.') }}</flux:text>
                <flux:button icon="list-bullet" :href="route('support.index', ['mine' => 1])" wire:navigate>
                    {{ __('View my requests') }}
                </flux:button>
            </flux:card>
        </div>
    @else
        <div class="max-w-md">
            <flux:input
                wire:model.live.debounce.400ms="search"
                :placeholder="__('Search guides, FAQs and troubleshooting...')"
                icon="magnifying-glass"
                clearable
            />
        </div>

        @if ($total === 0)
            <flux:card class="space-y-3 text-center">
                <flux:icon icon="book-open" class="mx-auto size-8 text-zinc-400" />
                <flux:heading level="2">{{ __('No help content is available yet.') }}</flux:heading>
                <flux:text>{{ __('Packages publish their guides here. In the meantime, you can contact support.') }}</flux:text>
                <flux:button variant="primary" x-data x-on:click="$dispatch('support-open')">
                    {{ __('Contact support') }}
                </flux:button>
            </flux:card>
        @elseif ($groups === [])
            <flux:card class="space-y-2 text-center">
                <flux:icon icon="magnifying-glass" class="mx-auto size-8 text-zinc-400" />
                <flux:heading level="2">{{ __('No results found') }}</flux:heading>
                <flux:text>{{ __('Try a different term or browse the categories.') }}</flux:text>
                <flux:button :href="route('help.index', ['tab' => $this->tab])" wire:navigate>
                    {{ __('Clear search') }}
                </flux:button>
            </flux:card>
        @else
            <div class="space-y-8">
                @foreach ($groups as $group => $entries)
                    <section class="space-y-3" aria-labelledby="help-group-{{ md5($group) }}">
                        <flux:heading level="2" id="help-group-{{ md5($group) }}">{{ __($group) }}</flux:heading>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">

                            @foreach ($entries as $entry)
                                @if ($entry['type'] === 'faq')
                                    <details class="group rounded-xl border border-zinc-200 bg-white p-4 open:shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                                        <summary class="flex cursor-pointer list-none items-start justify-between gap-3">
                                            <div class="space-y-1">
                                                <flux:badge size="sm" color="amber">{{ __('FAQ') }}</flux:badge>
                                                <flux:heading level="3" class="!text-base">{{ __($entry['title']) }}</flux:heading>
                                                <flux:text class="text-sm">{{ __($entry['summary']) }}</flux:text>
                                            </div>
                                            <flux:icon icon="chevron-down" variant="mini" class="mt-1 size-4 shrink-0 text-zinc-400 transition group-open:rotate-180" aria-hidden="true" />
                                        </summary>
                                        <div class="mt-4 border-t border-zinc-200 pt-4 text-sm leading-6 text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 [&_a]:font-medium [&_a]:text-accent-content [&_a]:underline [&_code]:rounded [&_code]:bg-zinc-100 [&_code]:px-1 dark:[&_code]:bg-zinc-800 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:ps-5 [&_p]:my-2 [&_ul]:my-2 [&_ul]:list-disc [&_ul]:ps-5">
                                            {!! $entryBodies[$entry['slug']] !!}
                                        </div>
                                        <a
                                            href="{{ route('help.index', array_filter(['tab' => $this->tab, 'search' => $this->search, 'topic' => $entry['slug']])) }}"
                                            wire:navigate
                                            class="mt-3 inline-flex text-xs font-medium text-accent-content underline underline-offset-2"
                                        >
                                            {{ __('Open permanent link') }}
                                        </a>
                                    </details>
                                @else
                                    <a
                                        href="{{ route('help.index', array_filter(['tab' => $this->tab, 'search' => $this->search, 'topic' => $entry['slug']])) }}"
                                        wire:navigate
                                        class="group flex flex-col gap-2 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-accent hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                                    >
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:badge size="sm" color="cyan">{{ __('Guide') }}</flux:badge>
                                            <flux:badge size="sm">{{ __($entry['group']) }}</flux:badge>
                                        </div>
                                        <flux:heading level="3" class="!text-base">{{ __($entry['title']) }}</flux:heading>
                                        <flux:text class="text-sm">{{ __($entry['summary']) }}</flux:text>
                                        <span class="mt-auto inline-flex items-center gap-1 pt-2 text-xs font-medium text-accent-content">
                                            {{ __('Read guide') }}
                                            <flux:icon icon="arrow-right" variant="mini" class="size-3.5 transition group-hover:translate-x-0.5" aria-hidden="true" />
                                        </span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    @endif
</section>

