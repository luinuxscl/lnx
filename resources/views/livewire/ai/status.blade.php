<section class="w-full">
    <flux:heading level="1">{{ __('AI') }}</flux:heading>
    <flux:text class="mt-1">{{ __('Configure and check the OpenRouter integration used by AI features.') }}</flux:text>

    @php
        $status = $status;
        $badge = fn (bool $ready, bool $enabled): array => match (true) {
            $ready => ['color' => 'green', 'icon' => 'check-circle', 'label' => __('Verified')],
            $enabled => ['color' => 'red', 'icon' => 'exclamation-triangle', 'label' => __('Error')],
            default => ['color' => 'amber', 'icon' => 'clock', 'label' => __('Pending')],
        };
        $statusBadge = $badge($status['ready'], $status['enabled']);
        $issueLabels = [
            'disabled' => __('AI is disabled. Enable it with AI_ENABLED=true.'),
            'missing_api_key' => __('The provider API key is not set (OPENROUTER_API_KEY).'),
            'provider_not_configured' => __('The provider is not configured.'),
            'missing_model' => __('No model is configured (AI_MODEL).'),
            'model_not_allowed' => __('The configured model is not in the allowlist.'),
        ];
        $categoryLabels = [
            'ok' => __('OK'),
            'not_configured' => __('Not configured'),
            'authentication' => __('Authentication failed'),
            'credits' => __('Insufficient credits or quota'),
            'model' => __('Model not compatible'),
            'timeout' => __('Timeout'),
            'unavailable' => __('Provider unavailable'),
            'rate_limited' => __('Rate limited by the provider'),
            'quota' => __('Local AI usage limit reached'),
            'unknown' => __('Unknown error'),
        ];
    @endphp

    <div class="mt-6 w-full max-w-3xl space-y-6">
        {{-- Configuration status --}}
        <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <flux:heading level="2">{{ __('Configuration') }}</flux:heading>
                <flux:badge :color="$statusBadge['color']" :icon="$statusBadge['icon']">{{ $statusBadge['label'] }}</flux:badge>
            </div>

            <flux:text>{{ __('Provider, model and limits come from the environment and config/lnx_ai.php. The API key is never shown.') }}</flux:text>

            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Provider') }}</dt>
                    <dd>{{ $status['provider'] }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Default model') }}</dt>
                    <dd>{{ $status['default_model'] ?: __('Not set') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('API key') }}</dt>
                    <dd>{{ $status['api_key_set'] ? __('Set') : __('Not set') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Timeout') }}</dt>
                    <dd>{{ $status['timeout'] }}s</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Maximum output tokens') }}</dt>
                    <dd>{{ $status['max_output_tokens'] }}</dd>
                </div>
            </dl>

            @if ($status['issues'] !== [])
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.text>{{ __('The integration is not ready:') }}</flux:callout.text>
                    <ul class="mt-2 list-disc space-y-1 ps-5 text-sm">
                        @foreach ($status['issues'] as $issue)
                            <li>{{ $issueLabels[$issue] ?? $issue }}</li>
                        @endforeach
                    </ul>
                </flux:callout>
            @endif
        </div>

        <flux:separator />

        {{-- Allowed models --}}
        <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading level="2">{{ __('Allowed models') }}</flux:heading>
            <flux:text>{{ __('Only models listed here can be used. Capabilities are validated on the server before any request.') }}</flux:text>

            @if ($status['allowed_models'] === [])
                <flux:callout icon="information-circle">
                    <flux:callout.text>{{ __('No models are allowed yet. Add the models this deployment uses to config/lnx_ai.php.') }}</flux:callout.text>
                </flux:callout>
            @else
                <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 text-start dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-2 text-start font-medium">{{ __('Model') }}</th>
                                <th class="px-4 py-2 text-start font-medium">{{ __('Capabilities') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($status['allowed_models'] as $model)
                                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                    <td class="px-4 py-2 font-mono text-xs">{{ $model['id'] }}</td>
                                    <td class="px-4 py-2">
                                        @php $enabledCapabilities = array_keys(array_filter($model['capabilities'], fn ($value) => (bool) $value)); @endphp
                                        {{ $enabledCapabilities === [] ? __('Plain text only') : implode(', ', $enabledCapabilities) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <flux:separator />

        {{-- Usage limits --}}
        <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <flux:heading level="2">{{ __('Usage limits') }}</flux:heading>
                <flux:button size="sm" :href="route('admin.ai.usage')" wire:navigate icon="chart-bar">
                    {{ __('View consumption') }}
                </flux:button>
            </div>
            <flux:text>{{ __('Limits come from the environment and config/lnx_ai.php. A request must satisfy both the per-user and per-application limit before the provider is called.') }}</flux:text>

            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-start dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-2 text-start font-medium">{{ __('Scope') }}</th>
                            <th class="px-4 py-2 text-start font-medium">{{ __('Window') }}</th>
                            <th class="px-4 py-2 text-start font-medium">{{ __('Requests') }}</th>
                            <th class="px-4 py-2 text-start font-medium">{{ __('Tokens') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-zinc-200 dark:border-zinc-700">
                            <td class="px-4 py-2">{{ __('Per user') }}</td>
                            <td class="px-4 py-2">{{ $status['user_limit']['window'] }}</td>
                            <td class="px-4 py-2">{{ $status['user_limit']['requests'] }}</td>
                            <td class="px-4 py-2">{{ number_format($status['user_limit']['tokens']) }}</td>
                        </tr>
                        @foreach ($status['application_limits'] as $app => $limit)
                            <tr wire:key="limit-{{ $app }}" class="border-t border-zinc-200 dark:border-zinc-700">
                                <td class="px-4 py-2 font-mono text-xs">{{ $app }}</td>
                                <td class="px-4 py-2">{{ $limit['window'] }}</td>
                                <td class="px-4 py-2">{{ $limit['requests'] }}</td>
                                <td class="px-4 py-2">{{ number_format($limit['tokens']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <flux:separator />

        {{-- Connection test --}}
        <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading level="2">{{ __('Connection test') }}</flux:heading>
            <flux:text>{{ __('Runs one limited request to the configured model. It is never triggered automatically; each run may be billable.') }}</flux:text>

            @error('connection_test')
                <flux:callout variant="danger">{{ $message }}</flux:callout>
            @enderror

            @if ($result !== [])
                @php
                    $succeeded = ($result['status'] ?? null) === 'success';
                    $resultIssues = $result['issues'] ?? [];
                @endphp
                <flux:callout :variant="$succeeded ? 'success' : 'danger'" :icon="$succeeded ? 'check-circle' : 'exclamation-triangle'">
                    <flux:callout.text>
                        {{ $categoryLabels[$result['category'] ?? 'unknown'] ?? __('Unknown error') }}
                        @if ($succeeded)
                            &mdash; {{ $result['model'] }} ({{ $result['duration_ms'] }} ms)
                            @if ($result['input_tokens'] !== null)
                                &mdash; {{ $result['input_tokens'] }} + {{ $result['output_tokens'] }} {{ __('tokens') }}
                            @endif
                        @endif
                    </flux:callout.text>
                    @if (! $succeeded && $resultIssues !== [])
                        <ul class="mt-2 list-disc space-y-1 ps-5 text-sm">
                            @foreach ($resultIssues as $issue)
                                <li>{{ $issueLabels[$issue] ?? $issue }}</li>
                            @endforeach
                        </ul>
                    @endif
                </flux:callout>
            @endif

            <flux:button wire:click="testConnection" wire:loading.attr="disabled" icon="bolt">
                {{ __('Test connection') }}
            </flux:button>
        </div>
    </div>
</section>
