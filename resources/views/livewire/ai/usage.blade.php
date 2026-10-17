<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('AI consumption')"
        :subtitle="__('Requests, tokens and cost recorded by the shared AI controls.')"
        icon="chart-bar"
        color="violet"
    />

    <flux:callout icon="information-circle">
        <flux:callout.text>{{ __('Costs reported by OpenRouter are the provider figure. Estimates come from configured rates and are not an invoice. A missing cost is shown as unknown, never as zero. Local limits do not cap the provider bill exactly.') }}</flux:callout.text>
    </flux:callout>

    @if ($summary !== null)
        <x-lnx-ui::statistics-panel
            :title="__('Summary')"
            :description="$priceNote !== null ? __('Pricing version: :version', ['version' => $priceNote]) : null"
        >
            <x-lnx-ui::summary-card
                class="col-span-12 sm:col-span-6 xl:col-span-4"
                :label="__('Attempts')"
                :value="\Illuminate\Support\Number::format($summary['events'])"
                icon="cursor-arrow-rays"
                color="violet"
            />
            <x-lnx-ui::summary-card
                class="col-span-12 sm:col-span-6 xl:col-span-4"
                :label="__('Tokens')"
                :value="\Illuminate\Support\Number::format($summary['tokens'])"
                icon="cpu-chip"
                color="cyan"
            />
            <x-lnx-ui::summary-card
                class="col-span-12 sm:col-span-6 xl:col-span-4"
                :label="__('Reported cost')"
                :value="\App\Support\NumberFormat::currency($summary['reported_cost'], $currency, 4)"
                icon="banknotes"
                color="emerald"
            />
            <x-lnx-ui::summary-card
                class="col-span-12 sm:col-span-6 xl:col-span-4"
                :label="__('Estimated cost')"
                :value="\App\Support\NumberFormat::currency($summary['estimated_cost'], $currency, 4)"
                icon="calculator"
                color="blue"
            />
            <x-lnx-ui::summary-card
                class="col-span-12 sm:col-span-6 xl:col-span-4"
                :label="__('Unknown cost')"
                :value="\Illuminate\Support\Number::format($summary['unknown_cost'])"
                icon="question-mark-circle"
                color="amber"
            />
            <x-lnx-ui::summary-card
                class="col-span-12 sm:col-span-6 xl:col-span-4"
                :label="__('Blocked / errors')"
                :value="\Illuminate\Support\Number::format($summary['blocked']).' / '.\Illuminate\Support\Number::format($summary['errors'])"
                icon="exclamation-triangle"
                color="rose"
            />
        </x-lnx-ui::statistics-panel>
    @endif

    <x-lnx-ui::data-table
        :title="__('Recorded requests')"
        :description="__('Every attempt recorded by the shared AI controls, with its tokens, cost and status.')"
        :filters-label="__('Filters')"
        :paginate="$events"
        {{-- Flux removes the padding of the first and last column (first:ps-0 / last:pe-0); restore a small breath. --}}
        table-class="[&_th:first-child]:ps-3 [&_td:first-child]:ps-3 [&_th:last-child]:pe-3 [&_td:last-child]:pe-3"
    >
        <x-slot:filters>
            <div class="w-full sm:w-44">
                <flux:input wire:model.live.debounce.300ms="user" :label="__('User')" :placeholder="__('Name, email or ID')" />
            </div>

            <div class="w-full sm:w-44">
                <flux:select wire:model.live="application" :label="__('Application')">
                    <flux:select.option value="">{{ __('All applications') }}</flux:select.option>
                    @foreach ($applications as $option)
                        <flux:select.option :value="$option" :key="$option">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="w-full sm:w-44">
                <flux:input wire:model.live.debounce.300ms="model" :label="__('Model')" :placeholder="__('Model identifier')" />
            </div>

            <div class="w-full sm:w-40">
                <flux:select wire:model.live="status" :label="__('Status')">
                    <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                    @foreach ($statuses as $option)
                        <flux:select.option :value="$option" :key="$option">{{ __(Illuminate\Support\Str::headline($option)) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="w-full sm:w-40">
                <flux:input wire:model.live="from" type="date" :label="__('From')" />
            </div>

            <div class="w-full sm:w-40">
                <flux:input wire:model.live="to" type="date" :label="__('To')" />
            </div>

            @if ($errors->any())
                <flux:callout variant="danger" icon="exclamation-triangle" class="w-full">
                    <flux:callout.heading>{{ __('Some filters are invalid') }}</flux:callout.heading>
                    <flux:callout.text>
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </flux:callout.text>
                </flux:callout>
            @endif
        </x-slot:filters>

        <x-slot:actions>
            @if ($this->hasActiveFilters())
                <flux:button icon="x-mark" wire:click="clearFilters">{{ __('Clear filters') }}</flux:button>
            @endif
        </x-slot:actions>

        {{-- No forced min-width: on narrow screens columns are hidden by breakpoint so the table always fits without horizontal scroll. --}}
        <flux:table.columns>
            <flux:table.column class="w-16 sm:w-36">{{ __('Date') }}</flux:table.column>
            <flux:table.column class="w-36 sm:w-40 lg:w-48 xl:w-56">{{ __('User') }}</flux:table.column>
            <flux:table.column class="w-20 max-md:hidden">{{ __('Application') }}</flux:table.column>
            <flux:table.column class="w-48 max-xl:hidden">{{ __('Model') }}</flux:table.column>
            <flux:table.column align="end" class="w-16 max-md:hidden">{{ __('Tokens') }}</flux:table.column>
            <flux:table.column align="end" class="w-28 max-sm:hidden">{{ __('Cost') }}</flux:table.column>
            <flux:table.column class="w-24 max-[380px]:hidden">{{ __('Status') }}</flux:table.column>
            <flux:table.column align="end" class="w-24 max-lg:hidden">{{ __('Duration') }}</flux:table.column>
            <flux:table.column align="end" class="w-16 max-2xl:hidden">{{ __('Attempt') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($events as $event)
                @php
                    $effective = $event->effective_model ?? $event->requested_model;
                    $costLabel = match (true) {
                        $event->reported_cost !== null => \App\Support\NumberFormat::currency($event->reported_cost, $event->cost_currency ?? $currency, 6),
                        $event->estimated_cost !== null => '≈ '.\App\Support\NumberFormat::currency($event->estimated_cost, $event->cost_currency ?? $currency, 6),
                        default => '—',
                    };
                    $costSource = match ($event->cost_source) {
                        \App\Models\AiUsageEvent::COST_REPORTED => ['label' => __('Reported'), 'color' => 'emerald'],
                        \App\Models\AiUsageEvent::COST_ESTIMATED => ['label' => __('Estimated'), 'color' => 'blue'],
                        default => ['label' => __('Unknown'), 'color' => 'zinc'],
                    };
                @endphp
                <flux:table.row :key="$event->id">
                    <flux:table.cell class="whitespace-nowrap text-zinc-500 dark:text-zinc-400">
                        <span class="hidden sm:inline">{{ $event->recorded_at->timezone(\App\Support\Appearance::currentTimezone())->format('Y-m-d H:i:s') }}</span>
                        <span class="sm:hidden">{{ $event->recorded_at->timezone(\App\Support\Appearance::currentTimezone())->format('d/m') }}</span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="block min-w-0" @if ($event->user_id !== null) title="{{ trim($event->user_email.' #'.$event->user_id) }}" @endif>
                            <x-lnx-ui::media-cell
                                :title="$event->user_name ?: __('Unknown user')"
                                :description="$event->user_id !== null ? trim($event->user_email.' #'.$event->user_id) : null"
                                icon="user"
                                color="blue"
                            />
                        </span>
                    </flux:table.cell>
                    <flux:table.cell class="max-md:hidden">{{ $event->application }}</flux:table.cell>
                    <flux:table.cell class="max-xl:hidden">
                        <span class="block truncate font-mono text-xs">{{ $effective ?: '—' }}</span>
                        @if ($event->requested_model !== null && $event->effective_model !== null && $event->requested_model !== $event->effective_model)
                            <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ __('Requested') }}: {{ $event->requested_model }}</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end" class="tabular-nums max-md:hidden">
                        {{ $event->total_tokens !== null ? \Illuminate\Support\Number::format((int) $event->total_tokens) : '—' }}
                    </flux:table.cell>
                    <flux:table.cell align="end" class="whitespace-nowrap max-sm:hidden">
                        <span class="tabular-nums">{{ $costLabel }}</span>
                        <span class="mt-1 block">
                            <flux:badge size="sm" :color="$costSource['color']">{{ $costSource['label'] }}</flux:badge>
                        </span>
                    </flux:table.cell>
                    <flux:table.cell class="max-[380px]:hidden">
                        <flux:badge :color="match ($event->status) {
                            \App\Models\AiUsageEvent::STATUS_SUCCESS => 'green',
                            \App\Models\AiUsageEvent::STATUS_BLOCKED => 'amber',
                            default => 'red',
                        }">
                            {{ __(Illuminate\Support\Str::headline($event->status)) }}
                        </flux:badge>
                        @if ($event->error_category !== null)
                            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ __(Illuminate\Support\Str::headline($event->error_category)) }}</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end" class="tabular-nums max-lg:hidden">
                        {{ $event->duration_ms !== null ? \Illuminate\Support\Number::format($event->duration_ms).' ms' : '—' }}
                    </flux:table.cell>
                    <flux:table.cell align="end" class="max-2xl:hidden">
                        <span class="text-xs text-zinc-500 dark:text-zinc-400" title="{{ $event->correlation_id }}">#{{ $event->attempt }}</span>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="9">
                        <div class="flex flex-col items-center gap-3 py-12 text-center">
                            <span class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800" aria-hidden="true">
                                <flux:icon icon="chart-bar" class="size-6 text-zinc-400" />
                            </span>
                            <flux:text class="font-medium">{{ __('No AI consumption recorded for these filters.') }}</flux:text>
                            @if ($this->hasActiveFilters())
                                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">{{ __('Clear filters') }}</flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </x-lnx-ui::data-table>
</section>
