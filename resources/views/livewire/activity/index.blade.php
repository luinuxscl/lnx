<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('Administrative activity')"
        :subtitle="__('Administrative changes are recorded from the installation of this feature.')"
        icon="clock"
        color="violet"
   >
        <x-slot:actions>
            <x-help-topic slug="lnx.activity" />
        </x-slot:actions>
    </x-lnx-ui::page-header>

    <x-lnx-ui::filter-bar>
        {{-- Texto libre (actor): no cabe en un chip-select, se queda inline (crm#16). --}}
        <x-lnx-ui::filter-control width="w-56">
            <flux:input
                size="sm"
                wire:model.live.debounce.300ms="actor"
                :placeholder="__('Name, email or ID')"
                icon="magnifying-glass"
                :aria-label="__('Actor')"
            />
        </x-lnx-ui::filter-control>

        <x-lnx-ui::filter-chip
            name="action"
            icon="adjustments-horizontal"
            :label="__('Action')"
            :options="collect($actions)->mapWithKeys(fn ($option) => [$option->value => __(Illuminate\Support\Str::headline($option->value))])"
            :any-label="__('All actions')"
            :active="$action !== ''"
        />

        {{-- Los rangos de fechas no caben en un chip-select: se quedan inline (crm#16). --}}
        <x-lnx-ui::filter-control width="w-40">
            <flux:input
                size="sm"
                wire:model.live="from"
                type="date"
                icon="calendar"
                :aria-label="__('From')"
            />
        </x-lnx-ui::filter-control>

        <x-lnx-ui::filter-control width="w-40">
            <flux:input
                size="sm"
                wire:model.live="to"
                type="date"
                icon="calendar"
                :aria-label="__('To')"
            />
        </x-lnx-ui::filter-control>

        {{-- ms-auto en un wrapper: el ítem del flex es el wrapper del tooltip,
            y una clase en el botón interior no empuja nada. --}}
        <div class="ms-auto flex items-center gap-2">
            @if ($this->hasActiveFilters())
                <flux:button
                    size="sm"
                    square
                    icon="funnel-x"
                    :tooltip="__('Clear filters')"
                    :aria-label="__('Clear filters')"
                    wire:click="clearFilters"
                />
            @endif

            <x-lnx-ui::table-per-page :options="[10, 25, 50]" :aria-label="__('Per page')" />
        </div>
    </x-lnx-ui::filter-bar>
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
            <flux:card>
                <div class="flex flex-col items-center gap-3 py-12 text-center">
                    <span class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800" aria-hidden="true">
                        <flux:icon icon="clock" class="size-6 text-zinc-400" />
                    </span>
                    <flux:text class="font-medium">{{ __('No administrative activity found.') }}</flux:text>
                    @if ($this->hasActiveFilters())
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">{{ __('Clear filters') }}</flux:button>
                    @endif
                </div>
            </flux:card>
        @endforelse
    </div>
    {{ $activities->links() }}
</section>
