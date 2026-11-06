<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamProposal;
use App\Ai\Sam\SamProposalResult;
use App\Ai\Sam\SamProposalUndoer;
use App\Models\User;

/**
 * Presenter that also keeps an undo, so the runtime can route one.
 */
class FakeUndoablePresenter extends FakeProposalPresenter implements SamProposalUndoer
{
    public ?SamProposalResult $undoResult = null;

    public int $undoCalls = 0;

    public ?string $undoneId = null;

    public function undo(SamProposal $proposal, User $actor): SamProposalResult
    {
        $this->undoCalls++;
        $this->undoneId = $proposal->id;

        return $this->undoResult ?? SamProposalResult::confirmed('Undone.');
    }
}
