<?php

namespace App\Ai\Sam;

/**
 * One thing the agent did during a turn, declared by a module.
 *
 * A trace entry describes a read, a proposal or a confirmation that already
 * happened: the module reads its own audit and translates it into a short,
 * bounded label. It never carries search terms, result content or anything the
 * actor could not see in the activity screen.
 *
 * @see SamTurnTrace
 */
final class SamTraceEntry
{
    public const KIND_READ = 'read';

    public const KIND_PROPOSAL = 'proposal';

    public const KIND_CONFIRMATION = 'confirmation';

    public const KIND_DENIED = 'denied';

    /**
     * @param  'read'|'proposal'|'confirmation'|'denied'  $kind
     */
    public function __construct(
        public readonly string $tool,
        public readonly string $label,
        public readonly string $kind = self::KIND_READ,
        public readonly string $status = 'success',
        public readonly ?string $resource = null,
        public readonly ?int $count = null,
        public readonly ?string $url = null,
    ) {}
}
