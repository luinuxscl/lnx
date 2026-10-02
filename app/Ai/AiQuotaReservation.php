<?php

namespace App\Ai;

/**
 * The counters a single request successfully reserved before the provider call.
 *
 * It is passed back to AiQuota so the reservation can be reconciled with the
 * actual usage, or released when no usage is known.
 */
readonly class AiQuotaReservation
{
    /**
     * @param  list<array{scope_type: string, scope_id: string, window: string, window_start: string, reserved_tokens: int}>  $counters
     */
    public function __construct(
        public string $correlationId,
        public string $application,
        public int $reservedTokens,
        public array $counters,
    ) {}
}
