<?php

namespace App\Ai\Sam;

use App\Models\User;

/**
 * Module side of a proposal: it resolves its own drafts for the panel and
 * executes the human confirmation.
 *
 * `confirm()` delegates in the module's confirmation action; it never writes
 * directly from a DTO, and it re-validates ownership, validity and the phrase.
 */
interface SamProposalPresenter
{
    /**
     * Drafts of this module with the given correlation, visible to the actor.
     *
     * @return list<SamProposal>
     */
    public function forCorrelation(string $correlationId, User $actor): array;

    /**
     * Confirm a draft after the person wrote the one-time phrase.
     */
    public function confirm(SamProposal $proposal, User $actor, string $phrase): SamProposalResult;
}
