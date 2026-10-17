<section class="w-full">
    <x-lnx-ui::page-header
        :title="__('Sent emails')"
        :subtitle="__('Review the emails the application has sent to users.')"
        icon="envelope"
        color="blue"
    />

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search emails...')" icon="magnifying-glass" />
        <flux:select wire:model.live="status" :label="__('Status')">
            <option value="all">{{ __('All') }}</option>
            <option value="sent">{{ __('Sent') }}</option>
            <option value="failed">{{ __('Failed') }}</option>
        </flux:select>
    </div>

    <div class="mt-6 overflow-x-auto">
        <flux:table :paginate="$emails" class="min-w-[42rem]">
            <flux:table.columns>
                <flux:table.column>{{ __('Recipient') }}</flux:table.column>
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Status') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($emails as $email)
                    <flux:table.row :key="$email->id">
                        <flux:table.cell>
                            <div class="font-medium">{{ $email->to_name ?? $email->user?->name ?? $email->to_email }}</div>
                            <flux:text class="text-xs">{{ $email->to_email }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>{{ $email->subject ?? __('No subject') }}</flux:table.cell>
                        <flux:table.cell>{{ ($email->sent_at ?? $email->created_at)->copy()->timezone($timezone)->format('Y-m-d H:i') }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:badge :color="$email->isSent() ? 'emerald' : 'rose'">{{ $email->isSent() ? __('Sent') : __('Failed') }}</flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="4">{{ __('No emails found.') }}</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
