<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="__('Sam')"
        :subtitle="__('Role ceilings for Sam.')"
        icon="sparkles"
    />

    <flux:callout variant="warning" icon="exclamation-triangle">
        <flux:callout.heading>{{ __('The deployment ceiling always wins.') }}</flux:callout.heading>
        <flux:callout.text>
            {{ __('If the deployment ceiling is read only, setting draft on a role does not change the effective mode.') }}
            {{ __('A higher role ceiling is stored and applies only after the deployment ceiling rises.') }}
        </flux:callout.text>
    </flux:callout>

    <flux:card class="flex items-center justify-between gap-4">
        <flux:text>{{ __('Deployment ceiling') }}</flux:text>
        <flux:badge>{{ __($global->label()) }}</flux:badge>
    </flux:card>

    @error('ceilings')
        <flux:callout variant="danger">{{ $message }}</flux:callout>
    @enderror

    <div class="space-y-3">
        @foreach ($rows as $row)
            <div wire:key="sam-ceiling-{{ $row['id'] }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <flux:select wire:model.live="ceilings.{{ $row['id'] }}" :label="$row['label']">
                            @foreach ($options as $option)
                                <flux:select.option :value="$option['value']" :key="$option['value']">{{ $option['label'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('ceilings.'.$row['id'])
                            <flux:text class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                        @enderror
                    </div>
                    <flux:button wire:click="save({{ $row['id'] }})" wire:loading.attr="disabled" wire:target="save({{ $row['id'] }})">
                        {{ __('Save') }}
                    </flux:button>
                </div>
                @if ($row['exceeds'])
                    <flux:callout variant="warning" icon="exclamation-triangle" class="mt-3">
                        <flux:callout.text>{{ __('This ceiling is above the deployment ceiling, so it does not change the effective mode.') }}</flux:callout.text>
                    </flux:callout>
                @endif
            </div>
        @endforeach
    </div>
</section>
