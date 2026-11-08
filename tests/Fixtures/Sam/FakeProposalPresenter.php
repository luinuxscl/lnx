<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamProposal;
use App\Ai\Sam\SamProposalPresenter;
use App\Ai\Sam\SamProposalResult;
use App\Models\User;

class FakeProposalPresenter implements SamProposalPresenter
{
    /**
     * @var array<string, list<SamProposal>>
     */
    public array $proposals = [];

    /**
     * @var list<SamProposal>
     */
    public array $pending = [];

    public ?SamProposalResult $result = null;

    public int $confirmCalls = 0;

    public function forCorrelation(string $correlationId, User $actor): array
    {
        return $this->proposals[$correlationId] ?? [];
    }

    public function pendingFor(User $actor, int $limit = 5): array
    {
        return array_slice($this->pending, 0, max(1, $limit));
    }

    public function confirm(SamProposal $proposal, User $actor, string $phrase): SamProposalResult
    {
        $this->confirmCalls++;

        return $this->result ?? SamProposalResult::confirmed('Confirmed.');
    }
}
