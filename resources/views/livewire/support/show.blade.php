<section class="mx-auto w-full max-w-4xl space-y-6">
    <x-lnx-ui::page-header
        :title="'#'.$ticket->id.' · '.$ticket->title"
        :subtitle="$ticket->user->name.' · '.$ticket->created_at->format('Y-m-d H:i')"
        icon="inbox"
        color="rose"
        :breadcrumbs="[['label' => __('Support requests'), 'href' => route('support.index')]]"
    >
        <x-slot:meta>
            <flux:badge>{{ __(str_replace('_', ' ', ucfirst($ticket->status))) }}</flux:badge>
        </x-slot:meta>
    </x-lnx-ui::page-header>
    <flux:card class="space-y-3"><flux:heading level="2">{{ __('Request') }}</flux:heading><flux:text class="whitespace-pre-wrap">{{ $ticket->description }}</flux:text><flux:text>{{ __('Type') }}: {{ __(ucfirst($ticket->type)) }} · {{ __('Screen') }}: {{ $ticket->screen ?: '—' }}</flux:text>@if ($ticket->resolution)<flux:callout><flux:callout.text>{{ __('Resolution') }}: {{ $ticket->resolution }}</flux:callout.text></flux:callout>@endif
        @foreach ($ticket->attachments as $attachment) @if (! $attachment->support_message_id)<a class="underline" href="{{ route('support.attachments.show', $attachment) }}" target="_blank">{{ $attachment->original_name }}</a>@endif @endforeach
    </flux:card>
    @if ($root)
        <flux:card class="space-y-4"><flux:heading level="2">{{ __('Manage request') }}</flux:heading><div class="grid gap-4 sm:grid-cols-2"><flux:select wire:model="priority" :label="__('Priority')"><option value="">{{ __('Select priority') }}</option>@foreach ($priorities as $value)<option value="{{ $value }}">{{ __(ucfirst($value)) }}</option>@endforeach</flux:select><flux:button wire:click="updatePriority">{{ __('Save priority') }}</flux:button></div><flux:select wire:model="status" :label="__('Status')">@foreach ($statuses as $value)<option value="{{ $value }}">{{ __(str_replace('_', ' ', ucfirst($value))) }}</option>@endforeach</flux:select><flux:textarea wire:model="resolution" :label="__('Resolution or closure explanation')" /><flux:error name="status" /><flux:error name="resolution" /><flux:button wire:click="updateStatus">{{ __('Update status') }}</flux:button></flux:card>
    @elseif ($ticket->status === 'resolved')
        <flux:button wire:click="reopen">{{ __('Reopen request') }}</flux:button>
    @endif
    <div class="space-y-3"><flux:heading level="2">{{ __('Conversation') }}</flux:heading>
        @foreach ($ticket->messages as $message)
            @if ($root || ! $message->internal)
                <flux:card wire:key="message-{{ $message->id }}" class="space-y-2 {{ $message->internal ? 'border-amber-400 bg-amber-50 dark:bg-amber-950' : '' }}"><div class="flex flex-wrap items-center gap-2"><flux:heading level="3">{{ $message->user->name }}</flux:heading>@if ($message->internal)<flux:badge color="amber">{{ __('Internal note · root only') }}</flux:badge>@endif<flux:text>{{ $message->created_at->format('Y-m-d H:i') }}</flux:text></div><flux:text class="whitespace-pre-wrap">{{ $message->body }}</flux:text>@foreach ($message->attachments as $attachment)<a class="underline" href="{{ route('support.attachments.show', $attachment) }}" target="_blank">{{ $attachment->original_name }}</a>@endforeach</flux:card>
            @endif
        @endforeach
    </div>
    @if ($root || $ticket->status !== 'closed')
        <form wire:submit="reply" class="space-y-4"><flux:heading level="2">{{ __('Reply') }}</flux:heading><flux:textarea wire:model="body" :label="__('Message')" rows="5" />@if ($root)<flux:checkbox wire:model="internal" :label="__('Internal note · root only')" />@endif<flux:field><flux:label>{{ __('Attach an image') }}</flux:label><input type="file" wire:model="image" accept="image/png,image/jpeg,image/webp"><flux:error name="image" /></flux:field><flux:button type="submit" variant="primary">{{ __('Send reply') }}</flux:button></form>
    @endif
</section>
