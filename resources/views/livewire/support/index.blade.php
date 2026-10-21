<section class="w-full space-y-6">
    <x-lnx-ui::page-header
        :title="$inbox ? __('Support inbox') : __('My requests')"
        :subtitle="__('Track your support requests here.')"
        icon="inbox"
        color="rose"
   >
        <x-slot:actions>
            <x-help-topic slug="lnx.feedback-support" />
        </x-slot:actions>
    </x-lnx-ui::page-header>
    @if ($inbox)
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <flux:select wire:model.live="status" :label="__('Status')"><option value="">{{ __('All') }}</option>@foreach ($statuses as $value)<option value="{{ $value }}">{{ __(str_replace('_', ' ', ucfirst($value))) }}</option>@endforeach</flux:select>
            <flux:select wire:model.live="type" :label="__('Type')"><option value="">{{ __('All') }}</option>@foreach ($types as $value)<option value="{{ $value }}">{{ __(ucfirst($value)) }}</option>@endforeach</flux:select>
            <flux:select wire:model.live="priority" :label="__('Priority')"><option value="">{{ __('All') }}</option>@foreach ($priorities as $value)<option value="{{ $value }}">{{ __(ucfirst($value)) }}</option>@endforeach</flux:select>
            <flux:input wire:model.live.debounce.300ms="author" :label="__('Author')" />
            <flux:input wire:model.live.debounce.300ms="screen" :label="__('Screen')" />
        </div>
    @endif
    <div class="space-y-3">
        @forelse ($tickets as $ticket)
            <flux:card wire:key="ticket-{{ $ticket->id }}" class="flex flex-wrap items-center justify-between gap-4">
                <div><flux:heading level="3"><a href="{{ route('support.show', $ticket) }}" wire:navigate class="hover:underline">#{{ $ticket->id }} · {{ $ticket->title }}</a></flux:heading><flux:text>{{ $ticket->user->name }} · {{ $ticket->created_at->format('Y-m-d H:i') }}</flux:text></div>
                <flux:badge>{{ __(str_replace('_', ' ', ucfirst($ticket->status))) }}</flux:badge>
            </flux:card>
        @empty
            <flux:card><flux:text>{{ __('No support requests found.') }}</flux:text></flux:card>
        @endforelse
    </div>
    {{ $tickets->links() }}
</section>
