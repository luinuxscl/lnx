<?php

namespace App\Ai\Exceptions;

use RuntimeException;

/**
 * Thrown before any provider call when a request would exceed a local quota.
 *
 * It never wraps a provider error: reaching a limit means the external request
 * is not made at all. The scope and metric let the UI explain the situation
 * without leaking configuration internals.
 */
class AiQuotaExceededException extends RuntimeException
{
    public const METRIC_REQUESTS = 'requests';

    public const METRIC_TOKENS = 'tokens';

    public function __construct(
        public readonly string $scopeType,
        public readonly string $application,
        public readonly string $window,
        public readonly string $metric,
    ) {
        parent::__construct(sprintf(
            'AI %s quota exceeded for the [%s] scope in the current %s window for application [%s].',
            $metric,
            $scopeType,
            $window,
            $application,
        ));
    }
}
