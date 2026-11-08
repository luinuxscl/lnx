<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Luinuxscl\LnxSam\SamRuntime;

/**
 * Pre-computes list-filter signals for one actor when proactive alerts are on.
 *
 * It never scans records outside the contributor's filtered query and it bails
 * out when the actor turned proactivity off before the job runs.
 */
class WarmSamFilterSignalsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $source,
        public readonly string $resource,
        public readonly array $filters,
    ) {}

    public function handle(SamRuntime $runtime): void
    {
        $actor = User::query()->find($this->userId);

        if (! $actor instanceof User) {
            return;
        }

        $runtime->warmFilterSignals($actor, $this->source, $this->resource, $this->filters);
    }
}
