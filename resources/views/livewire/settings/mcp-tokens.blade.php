<section class="mt-12">
    <flux:heading>{{ __('MCP access tokens') }}</flux:heading>
    <flux:subheading>{{ __('Connect authorized agents to the read-only MCP server') }}</flux:subheading>

    <div class="mt-6 flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
        @if ($plainText !== '')
            <flux:callout variant="warning" icon="key">
                <div class="space-y-3">
                    <flux:text>
                        {{ __('Copy this access token now. For security reasons it will not be shown again.') }}
                    </flux:text>

                    <div
                        class="flex items-stretch w-full border rounded-xl border-zinc-200 dark:border-zinc-700"
                        x-data="{
                            copied: false,
                            async copy() {
                                try {
                                    await navigator.clipboard.writeText('{{ $plainText }}');
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                } catch (e) {
                                    console.warn('Could not copy to clipboard');
                                }
                            }
                        }"
                    >
                        <input
                            type="text"
                            readonly
                            value="{{ $plainText }}"
                            class="w-full p-3 bg-transparent outline-none text-zinc-900 dark:text-zinc-100"
                        />

                        <button
                            type="button"
                            @click="copy()"
                            class="px-3 transition-colors border-l cursor-pointer border-zinc-200 dark:border-zinc-700"
                        >
                            <flux:icon.document-duplicate x-show="!copied" variant="outline" />
                            <flux:icon.check x-show="copied" variant="solid" class="text-green-500" />
                        </button>
                    </div>

                    <flux:button size="sm" variant="filled" wire:click="dismissToken">
                        {{ __('Done') }}
                    </flux:button>
                </div>
            </flux:callout>
        @endif

        <form wire:submit="createToken" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <flux:input
                    wire:model="name"
                    :label="__('Token name')"
                    placeholder="{{ __('e.g. Reporting agent') }}"
                    required
                />
            </div>

            <flux:button variant="primary" type="submit">{{ __('Create token') }}</flux:button>
        </form>

        <div class="border rounded-lg border-zinc-200 dark:border-zinc-700 overflow-hidden">
            @forelse ($tokens as $token)
                <div class="flex items-center justify-between p-4 {{ ! $loop->last ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}">
                    <div class="flex items-center gap-4">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.key class="size-5 text-zinc-500 dark:text-zinc-400" />
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2.5">
                                <p class="font-medium tracking-tight">{{ $token['name'] }}</p>
                                <flux:badge size="sm" :color="$token['is_active'] ? 'green' : 'zinc'">
                                    {{ __('Token '.$token['status']) }}
                                </flux:badge>
                            </div>
                            <p class="text-zinc-500 dark:text-zinc-400 text-xs">
                                {{ __('Created :time', ['time' => $token['created_at_diff']]) }}
                                @if ($token['last_used_at_diff'])
                                    <span class="opacity-50 mx-1">/</span>
                                    {{ __('Last used :time', ['time' => $token['last_used_at_diff']]) }}
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($token['is_active'])
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="trash"
                            icon:variant="outline"
                            wire:click="confirmRevoke({{ $token['id'] }})"
                            class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50"
                        />
                    @endif
                </div>
            @empty
                <div class="p-8 text-center">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.key class="size-7 text-zinc-400 dark:text-zinc-500" />
                    </div>
                    <p class="font-medium">{{ __('No access tokens yet') }}</p>
                    <flux:text class="mt-1">{{ __('Create a token to let an agent query the application') }}</flux:text>
                </div>
            @endforelse
        </div>
    </div>

    <flux:modal
        name="revoke-mcp-token-modal"
        class="max-w-md md:min-w-md"
        @close="closeRevokeModal"
        wire:model="showRevokeModal"
    >
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Revoke access token') }}</flux:heading>
                <flux:text>
                    {{ __('Are you sure you want to revoke the token ":name"? Any agent using it will lose access immediately.', ['name' => $revokingTokenName]) }}
                </flux:text>
            </div>

            <div class="flex gap-3 justify-end">
                <flux:button variant="outline" wire:click="closeRevokeModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="revoke">
                    {{ __('Revoke token') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
