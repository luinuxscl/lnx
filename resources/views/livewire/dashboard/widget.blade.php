@php
    /** @var \App\Support\Dashboard\DashboardWidget $widget */
    /** @var \App\Support\Dashboard\WidgetData $data */
    $span = (int) $widget->span();
    $spanClass = match ($span) {
        1 => 'col-span-12 xl:col-span-1',
        2 => 'col-span-12 xl:col-span-2',
        3 => 'col-span-12 sm:col-span-6 xl:col-span-3',
        4 => 'col-span-12 sm:col-span-6 xl:col-span-4',
        5 => 'col-span-12 xl:col-span-5',
        6 => 'col-span-12 sm:col-span-6',
        7 => 'col-span-12 xl:col-span-7',
        8 => 'col-span-12 xl:col-span-8',
        9 => 'col-span-12 xl:col-span-9',
        10 => 'col-span-12 xl:col-span-10',
        11 => 'col-span-12 xl:col-span-11',
        12 => 'col-span-12',
        default => 'col-span-12 sm:col-span-6 xl:col-span-4',
    };
@endphp

@switch($widget->type())
    @case(\App\Support\Dashboard\WidgetType::Stat)
        <x-lnx-ui::stat-card
            class="{{ $spanClass }}"
            :label="$data->label ?? $widget->title()"
            :aria-label="$data->ariaLabel ?? false"
            :value="$data->value"
            :icon="$data->icon"
            :color="$data->color"
            :href="$data->href"
        />
        @break

    @case(\App\Support\Dashboard\WidgetType::Stats)
        <x-lnx-ui::widget
            :title="$widget->title()"
            :description="$data->description ?? $widget->description()"
            :span="$span"
            :empty="$data->empty"
        >
            @if ($data->actions !== [])
                <x-slot:actions>
                    @foreach ($data->actions as $action)
                        <flux:button
                            size="sm"
                            :variant="$loop->last ? 'primary' : 'ghost'"
                            :icon="$action->icon"
                            :href="$action->href"
                            wire:navigate
                        >
                            {{ $action->label }}
                        </flux:button>
                    @endforeach
                </x-slot:actions>
            @endif

            @if ($data->items !== [])
                <div class="grid grid-cols-12 gap-4">
                    @foreach ($data->items as $item)
                        <x-lnx-ui::stat-card
                            class="col-span-12 sm:col-span-6 xl:col-span-3"
                            :label="$item->label"
                            :aria-label="$item->ariaLabel ?? false"
                            :value="$item->value"
                            :icon="$item->icon"
                            :color="$item->color"
                            :href="$item->href"
                        />
                    @endforeach
                </div>
            @endif
        </x-lnx-ui::widget>
        @break

    @case(\App\Support\Dashboard\WidgetType::Series)
        <x-lnx-ui::chart-card
            class="{{ $spanClass }}"
            :label="$widget->title()"
            :value="$data->value ?? ''"
            :description="$widget->description()"
            :change="$data->change"
            :series="$data->series"
            :labels="$data->labels"
            :chart="$data->chart"
            :color="$data->color"
        />
        @break

    @case(\App\Support\Dashboard\WidgetType::List)
        <x-lnx-ui::widget
            :title="$widget->title()"
            :description="$widget->description()"
            :span="$span"
            :href="$data->href"
            :empty="$data->empty"
        >
            @if ($data->items !== [])
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach (array_slice($data->items, 0, $data->limit) as $item)
                        <li class="py-2 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    @if (filled($item->href))
                                        <a
                                            href="{{ $item->href }}"
                                            class="text-sm font-medium text-zinc-900 hover:text-accent dark:text-zinc-100"
                                            wire:navigate
                                        >{{ $item->label }}</a>
                                    @else
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item->label }}</p>
                                    @endif

                                    @if (filled($item->description))
                                        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $item->description }}</p>
                                    @endif
                                </div>

                                @if (filled($item->badge) || filled($item->meta))
                                    <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $item->badge ?? $item->meta }}
                                    </span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-lnx-ui::widget>
        @break

    @case(\App\Support\Dashboard\WidgetType::Approvals)
        <x-lnx-ui::widget
            :title="$widget->title()"
            :description="$widget->description()"
            :span="$span"
            :href="$data->href"
            :empty="$data->empty"
        >
            @if ($data->items !== [])
                <ul class="space-y-3">
                    @foreach ($data->items as $item)
                        <li class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item->title }}</p>

                                    @if (filled($item->summary))
                                        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $item->summary }}</p>
                                    @endif

                                    @if ($item->changes !== [])
                                        <ul class="mt-1 list-inside list-disc text-xs text-zinc-500 dark:text-zinc-400">
                                            @foreach ($item->changes as $change)
                                                <li>{{ $change }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if (filled($item->expiresAt))
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $item->expiresAt }}</p>
                                    @endif
                                </div>

                                @if (filled($item->risk))
                                    <span class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ $item->risk }}
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                @if (filled($item->recordUrl))
                                    <flux:button size="xs" :href="$item->recordUrl" icon="arrow-right" wire:navigate>
                                        {{ __('Review') }}
                                    </flux:button>
                                @endif

                                @if ($item->canReject && filled($item->rejectUrl))
                                    <form method="POST" action="{{ $item->rejectUrl }}">
                                        @csrf
                                        <flux:button size="xs" variant="ghost" type="submit" icon="x-mark">
                                            {{ __('Discard') }}
                                        </flux:button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-lnx-ui::widget>
        @break

    @case(\App\Support\Dashboard\WidgetType::Action)
        <div class="{{ $spanClass }} space-y-3">
            @if (filled($widget->title()))
                <flux:heading level="3">{{ $widget->title() }}</flux:heading>
            @endif

            @if (filled($widget->description()))
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $widget->description() }}</flux:text>
            @endif

            @if ($data->items !== [])
                <div class="grid grid-cols-12 gap-4">
                    @foreach ($data->items as $item)
                        <x-lnx-ui::tool-card
                            class="col-span-12 sm:col-span-6 xl:col-span-4"
                            :title="$item->label"
                            :description="$item->description ?? ''"
                            :icon="$item->icon ?? 'arrow-right'"
                            :color="$item->color"
                            :href="$item->href ?? '#'"
                        />
                    @endforeach
                </div>
            @endif
        </div>
        @break

    @case(\App\Support\Dashboard\WidgetType::Timeline)
        <x-lnx-ui::timeline
            class="{{ $spanClass }}"
            :title="$widget->title()"
            :description="$widget->description()"
            :items="array_map(static fn ($item) => [
                'title' => $item->label,
                'description' => $item->description,
                'meta' => $item->meta,
                'icon' => $item->icon,
                'color' => $item->color,
            ], $data->items)"
            :empty="$data->empty"
        />
        @break

    @case(\App\Support\Dashboard\WidgetType::Text)
        <x-lnx-ui::widget
            :title="$widget->title()"
            :description="$widget->description()"
            :span="$span"
            :href="$data->href"
        >
            <div class="space-y-3">
                @if (filled($data->body))
                    <flux:text>{{ $data->body }}</flux:text>
                @endif

                @if ($data->actions !== [])
                    <div class="flex flex-wrap gap-2">
                        @foreach ($data->actions as $action)
                            <flux:button
                                size="sm"
                                :icon="$action->icon"
                                :href="$action->href"
                                wire:navigate
                            >
                                {{ $action->label }}
                            </flux:button>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-lnx-ui::widget>
        @break
@endswitch
