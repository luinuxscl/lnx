<section class="w-full space-y-6">
    <div>
        <flux:heading level="1">{{ __('Administrative activity') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Administrative changes are recorded from the installation of this feature.') }}</flux:text>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="actor" :label="__('Actor')" :placeholder="__('Name, email or ID')" />
        <flux:select wire:model.live="action" :label="__('Action')">
            <flux:select.option value="">{{ __('All actions') }}</flux:select.option>
            @foreach ($actions as $option)
                <flux:select.option :value="$option->value" :key="$option->value">{{ __(Illuminate\Support\Str::headline($option->value)) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model.live="from" type="date" :label="__('From')" />
        <flux:input wire:model.live="to" type="date" :label="__('To')" />
    </div>
    @error('action') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror
    @error('from') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror
    @error('to') <flux:callout variant="danger">{{ $message }}</flux:callout> @enderror

    <div class="space-y-3">
        @forelse ($activities as $activity)
            <div wire:key="activity-{{ $activity->id }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="grid gap-2 sm:grid-cols-4">
                    <div><flux:text class="font-medium">{{ $activity->occurred_at->timezone(\App\Support\Appearance::currentTimezone())->format('Y-m-d H:i:s') }}</flux:text></div>
                    <div><flux:text>{{ $activity->actor_name ?? __('Unknown actor') }}</flux:text><flux:text class="text-xs">{{ $activity->actor_email ?? '' }} @if ($activity->actor_id !== null) #{{ $activity->actor_id }} @endif</flux:text></div>
                    <div><flux:badge>{{ __(Illuminate\Support\Str::headline($activity->action->value)) }}</flux:badge></div>
                    <div><flux:text>{{ __(ucfirst($activity->resource_type)) }} #{{ $activity->resource_id }} · {{ $activity->resource_name ?? $activity->resource_email ?? '' }}</flux:text></div>
                </div>
                @if ($activity->changes)
                    <details class="mt-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                        <summary class="cursor-pointer text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Show changes') }}</summary>
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full min-w-96 text-left text-sm">
                                <thead><tr><th class="p-2">{{ __('Field') }}</th><th class="p-2">{{ __('Previous value') }}</th><th class="p-2">{{ __('New value') }}</th></tr></thead>
                                <tbody>
                                    @foreach ($activity->changes as $field => $change)
                                        <tr wire:key="change-{{ $activity->id }}-{{ $field }}" class="border-t border-zinc-200 dark:border-zinc-700">
                                            <td class="p-2">{{ __(Illuminate\Support\Str::headline($field)) }}</td>
                                            <td class="break-all p-2">{{ is_bool($change['old'] ?? null) ? (($change['old'] ?? false) ? __('Yes') : __('No')) : ($change['old'] ?? '—') }}</td>
                                            <td class="break-all p-2">{{ is_bool($change['new'] ?? null) ? (($change['new'] ?? false) ? __('Yes') : __('No')) : ($change['new'] ?? '—') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endif
            </div>
        @empty
            <flux:text>{{ __('No administrative activity found.') }}</flux:text>
        @endforelse
    </div>
    {{ $activities->links() }}
</section>
