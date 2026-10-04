<div x-data="{
    preview: null,
    open() { $wire.open({ screen: @js(request()->route()?->getName()), route_name: @js(request()->route()?->getName()), width: window.innerWidth, height: window.innerHeight, theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }) },
    async capture() {
        const panel = this.$refs.panel;
        let stream = null;
        panel.style.visibility = 'hidden';
        try {
            stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
            const video = document.createElement('video');
            video.srcObject = stream;
            await video.play();
            await new Promise(resolve => setTimeout(resolve, 250));
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            this.preview = canvas.toDataURL('image/png');
        } catch (error) { this.preview = null }
        finally { stream?.getTracks().forEach(track => track.stop()); panel.style.visibility = 'visible' }
    },
    useCapture() {
        fetch(this.preview).then(response => response.blob()).then(blob => {
            $wire.upload('image', new File([blob], 'capture.png', { type: 'image/png' }));
            this.preview = null;
        });
    },
    paste(event) {
        const item = Array.from(event.clipboardData?.items || []).find(item => item.type.startsWith('image/'));
        if (item) { const file = item.getAsFile(); if (file) $wire.upload('image', file) }
    }
}" x-on:support-open.window="open()" x-on:paste="paste($event)">
    <div x-ref="panel">
        <flux:modal name="support-panel" wire:model.self="showPanel" class="max-w-xl">
            <form wire:submit="submit" class="space-y-5">
                <flux:heading level="2">{{ __('Feedback and support') }}</flux:heading>
                <flux:text>{{ __('Tell us what happened or what you would like to improve.') }}</flux:text>
                <flux:select wire:model="type" :label="__('Type')">
                    <flux:select.option value="problem">{{ __('Problem') }}</flux:select.option>
                    <flux:select.option value="idea">{{ __('Improvement idea') }}</flux:select.option>
                    <flux:select.option value="question">{{ __('Question') }}</flux:select.option>
                </flux:select>
                <flux:input wire:model="title" :label="__('Title')" />
                <flux:textarea wire:model="description" :label="__('Description')" rows="5" />
                <flux:select wire:model="blocking" :label="__('Does this prevent you from continuing your work?')">
                    <flux:select.option value="">{{ __('Prefer not to say') }}</flux:select.option>
                    <flux:select.option value="1">{{ __('Yes') }}</flux:select.option>
                    <flux:select.option value="0">{{ __('No') }}</flux:select.option>
                </flux:select>
                <div class="flex flex-wrap gap-3">
                    <flux:button type="button" wire:click="suggest" wire:loading.attr="disabled" wire:target="suggest" icon="sparkles">{{ __('Improve writing with AI') }}</flux:button>
                    <flux:button type="button" x-on:click="capture()">{{ __('Capture screen') }}</flux:button>
                </div>
                <flux:text class="text-xs">{{ __('Only the description is sent to AI. Images are never sent to AI.') }}</flux:text>
                @if ($aiError)<flux:callout variant="warning"><flux:callout.text>{{ $aiError }}</flux:callout.text></flux:callout>@endif
                @if ($proposalTitle)
                    <flux:card class="space-y-3">
                        <flux:heading level="3">{{ __('Suggestion') }}</flux:heading>
                        <flux:text>{{ $proposalTitle }}</flux:text>
                        <flux:text class="whitespace-pre-wrap">{{ $proposalDescription }}</flux:text>
                        <div class="flex gap-3"><flux:button type="button" wire:click="acceptProposal">{{ __('Accept') }}</flux:button><flux:button type="button" wire:click="discardProposal">{{ __('Discard') }}</flux:button></div>
                    </flux:card>
                @endif
                <template x-if="preview"><div class="space-y-2"><img :src="preview" :alt="@js(__('Screen capture preview'))" class="max-h-56 rounded border"><div class="flex gap-2"><flux:button type="button" x-on:click="useCapture()">{{ __('Attach capture') }}</flux:button><flux:button type="button" x-on:click="preview = null">{{ __('Discard') }}</flux:button></div></div></template>
                <flux:field><flux:label>{{ __('Attach an image') }}</flux:label><input type="file" accept="image/png,image/jpeg,image/webp" wire:model="image" class="block w-full text-sm"><flux:error name="image" /></flux:field>
                @if ($image && in_array($image->getMimeType(), ['image/png', 'image/jpeg', 'image/webp'], true))<img src="{{ $image->temporaryUrl() }}" alt="{{ __('Attachment preview') }}" class="max-h-40 rounded border">@endif
                @if ($image)<flux:button type="button" wire:click="$set('image', null)">{{ __('Discard attachment') }}</flux:button>@endif
                <div class="flex justify-end gap-3"><flux:button type="button" x-on:click="$wire.showPanel = false">{{ __('Cancel') }}</flux:button><flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="submit">{{ __('Send request') }}</flux:button></div>
            </form>
        </flux:modal>
    </div>
</div>
