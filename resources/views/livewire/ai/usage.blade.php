<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('AI consumption')"
        :subtitle="__('Requests, tokens and cost recorded by the shared AI controls.')"
        icon="chart-bar"
        color="violet"
        :breadcrumbs="[['label' => __('AI'), 'href' => route('admin.ai.index')]]"
    />

    <flux:callout icon="information-circle">
        <flux:callout.text>{{ __('Costs reported by OpenRouter are the provider figure. Estimates come from configured rates and are not an invoice. A missing cost is shown as unknown, never as zero. Local limits do not cap the provider bill exactly.') }}</flux:callout.text>
    </flux:callout>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="user" :label="__('User')" :placeholder="__('Name, email or ID')" />
        <flux:select wire:model.live="application" :label="__('Application')">
            <flux:select.option value="">{{ __('All applications') }}</flux:select.option>
            @foreach ($applications as $option)
                <flux:select.option :value="$option" :key="$option">{{ $option }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model.live.debounce.300ms="model" :label="__('Model')" :placeholder="__('Model identifier')" />
        <flux:select wire:model.live="status" :label="__('Status')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($statuses as $option)
                <flux:select.option :value="$option" :key="$option">{{ __(Illuminate\Support\Str::headline($option)) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model.live="from" type="date" :label="__('From')" />
        <flux:input wire:model.live="to" type="date" :label="__('To')" />
    </div>
    @error('user') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror
    @error('application') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror
    @error('model') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror
    @error('status') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror
    @error('from') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror
    @error('to') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror

    @if ($summary !== null)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <flux:card class="space-y-1">
                <flux:text>{{ __('Attempts') }}</flux:text>
                <flux:heading level="3">{{ \Illuminate\Support\Number::format($summary['events']) }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text>{{ __('Tokens') }}</flux:text>
                <flux:heading level="3">{{ \Illuminate\Support\Number::format($summary['tokens']) }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text>{{ __('Reported cost') }}</flux:text>
                <flux:heading level="3">{{ \App\Support\NumberFormat::currency($summary['reported_cost'], $currency, 4) }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text>{{ __('Estimated cost') }}</flux:text>
                <flux:heading level="3">{{ \App\Support\NumberFormat::currency($summary['estimated_cost'], $currency, 4) }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text>{{ __('Unknown cost') }}</flux:text>
                <flux:heading level="3">{{ \Illuminate\Support\Number::format($summary['unknown_cost']) }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text>{{ __('Blocked / errors') }}</flux:text>
                <flux:heading level="3">{{ \Illuminate\Support\Number::format($summary['blocked']) }} / {{ \Illuminate\Support\Number::format($summary['errors']) }}</flux:heading>
            </flux:card>
        </div>
        @if ($priceNote !== null)
            <flux:text class="text-xs">{{ __('Pricing version: :version', ['version' => $priceNote]) }}</flux:text>
        @endif
    @endif

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full min-w-4xl text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-3 py-2 font-medium">{{ __('Date') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('User') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Application') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Model') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Tokens') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Cost') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Status') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Duration') }}</th>
                    <th class="px-3 py-2 font-medium">{{ __('Attempt') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    @php
                        $effective = $event->effective_model ?? $event->requested_model;
                        $cost = $event->reported_cost ?? $event->estimated_cost;
                        $costLabel = match (true) {
                            $event->reported_cost !== null => \App\Support\NumberFormat::currency($event->reported_cost, $event->cost_currency ?? $currency, 6),
                            $event->estimated_cost !== null => '≈ '.\App\Support\NumberFormat::currency($event->estimated_cost, $event->cost_currency ?? $currency, 6),
                            default => '—',
                        };
                        $costSource = match ($event->cost_source) {
                            \App\Models\AiUsageEvent::COST_REPORTED => __('Reported'),
                            \App\Models\AiUsageEvent::COST_ESTIMATED => __('Estimated'),
                            default => __('Unknown'),
                        };
                    @endphp
                    <tr wire:key="ai-usage-{{ $event->id }}" class="border-t border-zinc-200 dark:border-zinc-700">
                        <td class="whitespace-nowrap px-3 py-2">{{ $event->recorded_at->timezone(\App\Support\Appearance::currentTimezone())->format('Y-m-d H:i:s') }}</td>
                        <td class="px-3 py-2">
                            {{ $event->user_name ?? __('Unknown user') }}
                            @if ($event->user_id !== null)
                                <span class="block text-xs text-zinc-500">{{ $event->user_email }} #{{ $event->user_id }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $event->application }}</td>
                        <td class="px-3 py-2 font-mono text-xs">
                            {{ $effective ?: '—' }}
                            @if ($event->requested_model !== null && $event->effective_model !== null && $event->requested_model !== $event->effective_model)
                                <span class="block text-zinc-500">{{ __('Requested') }}: {{ $event->requested_model }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $event->total_tokens !== null ? \Illuminate\Support\Number::format((int) $event->total_tokens) : '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ $costLabel }}
                            <span class="block text-xs text-zinc-500">{{ $costSource }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <flux:badge :color="match ($event->status) {
                                \App\Models\AiUsageEvent::STATUS_SUCCESS => 'green',
                                \App\Models\AiUsageEvent::STATUS_BLOCKED => 'amber',
                                default => 'red',
                            }">
                                {{ __(Illuminate\Support\Str::headline($event->status)) }}
                            </flux:badge>
                            @if ($event->error_category !== null)
                                <span class="block text-xs text-zinc-500">{{ __(Illuminate\Support\Str::headline($event->error_category)) }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $event->duration_ms !== null ? \Illuminate\Support\Number::format($event->duration_ms).' ms' : '—' }}</td>
                        <td class="px-3 py-2 text-xs text-zinc-500" title="{{ $event->correlation_id }}">#{{ $event->attempt }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-6 text-center text-zinc-500">{{ __('No AI consumption recorded for these filters.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $events->links() }}
</section>
