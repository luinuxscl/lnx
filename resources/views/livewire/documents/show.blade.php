<div class="flex flex-col gap-6 p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl" level="1">{{ $document->original_filename }}</flux:heading>
                @if ($document->isPending())
                    <flux:badge color="zinc">{{ __('Pending') }}</flux:badge>
                @elseif ($document->isProcessing())
                    <flux:badge color="sky">{{ __('Processing') }}</flux:badge>
                @elseif ($document->isReviewRequired())
                    <flux:badge color="amber">{{ __('Review Required') }}</flux:badge>
                @elseif ($document->isConfirmed())
                    <flux:badge color="emerald">{{ __('Confirmed') }}</flux:badge>
                @elseif ($document->isFailed())
                    <flux:badge color="red">{{ __('Failed') }}</flux:badge>
                @endif
            </div>
            <flux:subheading>{{ __('Uploaded :date', ['date' => $document->created_at->format('Y-m-d H:i')]) }}</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="subtle" icon="arrow-down-tray" :href="route('documents.download', $document)" target="_blank">
                {{ __('Download original file') }}
            </flux:button>
            <flux:button variant="subtle" icon="arrow-path" wire:click="reprocess">
                {{ __('Re-process') }}
            </flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:badge color="lime" size="lg" class="w-full justify-start p-4">
            {{ session('status') }}
        </flux:badge>
    @endif

    @if ($document->isFailed())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/30 text-red-800 dark:text-red-300">
            <flux:heading size="md" color="red" class="mb-1">{{ __('Processing failed') }}</flux:heading>
            <flux:text color="red">{{ $document->error_message ?? __('An error occurred while extracting text from the document.') }}</flux:text>
        </div>
    @endif

    @if ($document->validation_notes && ($document->validation_notes['has_warnings'] ?? false))
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/30 text-amber-800 dark:text-amber-300">
            <flux:heading size="md" color="amber" class="mb-1">{{ __('Human review required') }}</flux:heading>
            <flux:text color="amber">{{ __('Some fields could not be automatically extracted with high confidence or were missing. Please review and verify the values below.') }}</flux:text>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Review Form Column -->
        <div class="lg:col-span-2 flex flex-col gap-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">{{ __('Extracted data & human review') }}</flux:heading>

                <form wire:submit="confirm" class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:input wire:model="document_type" label="{{ __('Document type') }}" placeholder="invoice, receipt, contract, etc." />
                        </div>
                        <div>
                            <flux:input wire:model="document_number" label="{{ __('Document number') }}" placeholder="INV-12345" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <flux:input type="date" wire:model="date" label="{{ __('Date') }}" />
                        </div>
                        <div>
                            <flux:input type="number" step="0.01" wire:model="total_amount" label="{{ __('Total amount') }}" />
                        </div>
                        <div>
                            <flux:input wire:model="currency" label="{{ __('Currency') }}" placeholder="USD, EUR, CLP" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:input wire:model="issuer_name" label="{{ __('Issuer / Vendor name') }}" />
                        </div>
                        <div>
                            <flux:input wire:model="recipient_name" label="{{ __('Recipient name') }}" />
                        </div>
                    </div>

                    <div>
                        <flux:textarea wire:model="summary" label="{{ __('Summary') }}" rows="3" />
                    </div>

                    <div class="mt-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <div class="flex items-center justify-between mb-3">
                            <flux:heading size="md">{{ __('Line items') }}</flux:heading>
                            <flux:button size="sm" variant="subtle" icon="plus" wire:click="addLineItem">
                                {{ __('Add line item') }}
                            </flux:button>
                        </div>

                        @if (empty($line_items))
                            <flux:text class="italic text-sm">{{ __('No line items extracted.') }}</flux:text>
                        @else
                            <div class="flex flex-col gap-3">
                                @foreach ($line_items as $index => $item)
                                    <div class="flex items-center gap-2 bg-zinc-50 p-3 rounded-lg dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                                        <div class="flex-1">
                                            <flux:input wire:model="line_items.{{ $index }}.description" placeholder="{{ __('Description') }}" />
                                        </div>
                                        <div class="w-24">
                                            <flux:input type="number" step="0.01" wire:model="line_items.{{ $index }}.quantity" placeholder="{{ __('Qty') }}" />
                                        </div>
                                        <div class="w-28">
                                            <flux:input type="number" step="0.01" wire:model="line_items.{{ $index }}.price" placeholder="{{ __('Price') }}" />
                                        </div>
                                        <flux:button size="sm" variant="subtle" color="red" icon="trash" wire:click="removeLineItem({{ $index }})" />
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <flux:button variant="subtle" wire:click="saveDraft">
                            {{ __('Save draft') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary" icon="check-circle">
                            {{ __('Confirm document') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Raw Text Preview Column -->
        <div class="flex flex-col gap-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-2">{{ __('Raw extracted text') }}</flux:heading>
                <flux:text class="mb-4 text-xs">{{ __('Text extracted from document file before AI processing.') }}</flux:text>

                @if ($document->extracted_text)
                    <div class="max-h-96 overflow-y-auto rounded-lg bg-zinc-50 p-3 font-mono text-xs text-zinc-800 dark:bg-zinc-950 dark:text-zinc-200 whitespace-pre-wrap border border-zinc-200 dark:border-zinc-800">
                        {{ $document->extracted_text }}
                    </div>
                @else
                    <flux:text class="italic text-sm">{{ __('No raw text available.') }}</flux:text>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md" class="mb-2">{{ __('Document metadata') }}</flux:heading>
                <dl class="flex flex-col gap-2 text-xs">
                    <div class="flex justify-between border-b border-zinc-100 pb-1 dark:border-zinc-800">
                        <dt class="text-zinc-500">{{ __('MIME type') }}:</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $document->mime_type }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-zinc-100 pb-1 dark:border-zinc-800">
                        <dt class="text-zinc-500">{{ __('Page count') }}:</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ \Illuminate\Support\Number::format($document->page_count ?? 1) }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-zinc-100 pb-1 dark:border-zinc-800">
                        <dt class="text-zinc-500">{{ __('File size') }}:</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ \App\Support\NumberFormat::fileSize($document->file_size) }}</dd>
                    </div>
                    @if ($document->correlation_id)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">{{ __('AI Correlation ID') }}:</dt>
                            <dd class="font-mono text-zinc-900 dark:text-zinc-100 truncate max-w-36">{{ $document->correlation_id }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
