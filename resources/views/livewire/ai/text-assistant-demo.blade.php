<section class="w-full">
    <x-lnx-ui::page-header
        :title="__('Text assistant')"
        :subtitle="__('Try the reusable assistant on a scratch field. It applies changes to the form only when you accept them.')"
        icon="sparkles"
        color="violet"
        :breadcrumbs="[['label' => __('AI'), 'href' => route('admin.ai.index')]]"
    />

    <div class="mt-6 w-full max-w-5xl space-y-6">
        <livewire:ai.text-assistant wire:model="text" />

        <div class="space-y-2 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading level="2">{{ __('Form value') }}</flux:heading>
            <flux:text>{{ __('This is what a business form would receive. It only changes when you accept a proposal.') }}</flux:text>
            <pre class="whitespace-pre-wrap break-words rounded bg-zinc-50 p-3 text-sm dark:bg-zinc-800">{{ $text }}</pre>
        </div>
    </div>
</section>
