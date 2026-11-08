<?php

namespace Tests\Fixtures\Sam;

use App\Models\User;
use Luinuxscl\LnxSam\SamProposal;
use Luinuxscl\LnxSam\SamProposalResult;
use Luinuxscl\LnxSam\SamProposalUndoer;

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
