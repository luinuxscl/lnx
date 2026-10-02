<?php

namespace App\Ai;

use App\Ai\Exceptions\AiQuotaExceededException;
use App\Models\AiQuotaCounter;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Atomic, database-backed quota reservation for AI requests.
 *
 * Before a provider call, a request reserves one request slot and the maximum
 * output tokens for every applicable scope (user and application). The
 * counters are updated inside a transaction with a locked row, so concurrent
 * requests cannot both pass a limit at the boundary. Once the provider replies,
 * the reservation is reconciled with the actual token usage, or released when
 * no usage is known. A missing row means no available quota, never a free pass.
 */
class AiQuota
{
    public function __construct(private readonly AiConfig $config) {}

    /**
     * Reserve quota for a request, or throw when a limit would be exceeded.
     *
     * @throws AiQuotaExceededException
     */
    public function reserve(?User $user, string $application, int $reservedTokens, ?string $correlationId = null): AiQuotaReservation
    {
        $application = $this->config->normalizeApplication($application);
        $reservedTokens = max(0, $reservedTokens);
        $correlationId ??= (string) str()->uuid();

        $scopes = $this->scopesFor($user, $application);

        $counters = DB::transaction(function () use ($scopes, $application, $reservedTokens): array {
            $reserved = [];

            foreach ($scopes as $scope) {
                $limit = $scope['limit'];

                $checkRequests = $limit['requests'] > 0;
                $checkTokens = $limit['tokens'] > 0;

                if (! $checkRequests && ! $checkTokens) {
                    continue;
                }

                $windowStart = $this->windowStart($limit['window']);

                $keys = [
                    'scope_type' => $scope['scope_type'],
                    'scope_id' => $scope['scope_id'],
                    'window' => $limit['window'],
                    'window_start' => $windowStart,
                ];

                DB::table('ai_quota_counters')->upsert(
                    [[
                        ...$keys,
                        'requests' => 0,
                        'tokens' => 0,
                        'reserved_tokens' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]],
                    ['scope_type', 'scope_id', 'window', 'window_start'],
                    ['updated_at'],
                );

                $row = DB::table('ai_quota_counters')
                    ->where($keys)
                    ->lockForUpdate()
                    ->first();

                if ($row === null) {
                    throw new AiQuotaExceededException(
                        $scope['scope_type'],
                        $application,
                        $limit['window'],
                        AiQuotaExceededException::METRIC_REQUESTS,
                    );
                }

                if ($checkRequests && ($row->requests + 1) > $limit['requests']) {
                    throw new AiQuotaExceededException(
                        $scope['scope_type'],
                        $application,
                        $limit['window'],
                        AiQuotaExceededException::METRIC_REQUESTS,
                    );
                }

                if ($checkTokens && ($row->tokens + $row->reserved_tokens + $reservedTokens) > $limit['tokens']) {
                    throw new AiQuotaExceededException(
                        $scope['scope_type'],
                        $application,
                        $limit['window'],
                        AiQuotaExceededException::METRIC_TOKENS,
                    );
                }

                DB::table('ai_quota_counters')->where($keys)->update([
                    'requests' => $row->requests + 1,
                    'reserved_tokens' => $row->reserved_tokens + $reservedTokens,
                    'updated_at' => now(),
                ]);

                $reserved[] = [
                    'scope_type' => $scope['scope_type'],
                    'scope_id' => $scope['scope_id'],
                    'window' => $limit['window'],
                    'window_start' => $windowStart->toDateTimeString(),
                    'reserved_tokens' => $reservedTokens,
                ];
            }

            return $reserved;
        });

        return new AiQuotaReservation($correlationId, $application, $reservedTokens, $counters);
    }

    /**
     * Reconcile a reservation with the actual usage.
     *
     * The reserved output tokens are always released. When the actual total is
     * known it is added to the consumed counter; when it is unknown the
     * reservation is simply released so a failure never blocks later requests.
     */
    public function reconcile(AiQuotaReservation $reservation, ?int $actualTotalTokens): void
    {
        $this->settle($reservation, $actualTotalTokens);
    }

    /**
     * Release a reservation without recording consumption.
     */
    public function release(AiQuotaReservation $reservation): void
    {
        $this->settle($reservation, null);
    }

    /**
     * Apply the reservation outcome to every counter, atomically per counter.
     */
    protected function settle(AiQuotaReservation $reservation, ?int $actualTotalTokens): void
    {
        DB::transaction(function () use ($reservation, $actualTotalTokens): void {
            foreach ($reservation->counters as $counter) {
                $keys = [
                    'scope_type' => $counter['scope_type'],
                    'scope_id' => $counter['scope_id'],
                    'window' => $counter['window'],
                    'window_start' => $counter['window_start'],
                ];

                $row = DB::table('ai_quota_counters')->where($keys)->lockForUpdate()->first();

                if ($row === null) {
                    continue;
                }

                $reserved = max(0, (int) $row->reserved_tokens - $counter['reserved_tokens']);

                DB::table('ai_quota_counters')->where($keys)->update([
                    'reserved_tokens' => $reserved,
                    'tokens' => (int) $row->tokens + max(0, (int) $actualTotalTokens),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * The quota scopes a request is checked against.
     *
     * @return list<array{scope_type: string, scope_id: string, limit: array{window: string, requests: int, tokens: int}}>
     */
    protected function scopesFor(?User $user, string $application): array
    {
        $scopes = [];

        if ($user !== null) {
            $scopes[] = [
                'scope_type' => AiQuotaCounter::SCOPE_USER,
                'scope_id' => (string) $user->getKey(),
                'limit' => $this->config->userLimit(),
            ];
        }

        $scopes[] = [
            'scope_type' => AiQuotaCounter::SCOPE_APPLICATION,
            'scope_id' => $application,
            'limit' => $this->config->applicationLimit($application),
        ];

        return $scopes;
    }

    /**
     * The start of the window that contains the given moment.
     */
    protected function windowStart(string $window): CarbonInterface
    {
        return match ($window) {
            'minute' => now()->startOfMinute(),
            'day' => now()->startOfDay(),
            default => now()->startOfHour(),
        };
    }
}
