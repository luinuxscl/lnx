<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamContext;
use App\Ai\Sam\SamContributor;
use App\Ai\Sam\SamProposalPresenter;
use App\Models\User;
use Illuminate\Http\Request;

class SecondFakeSamContributor implements SamContributor
{
    public function source(): string
    {
        return 'fake';
    }

    public function label(): string
    {
        return 'Second fake module';
    }

    public function knowledge(User $actor): ?string
    {
        return null;
    }

    public function readTools(User $actor): array
    {
        return [];
    }

    public function proposeTools(User $actor): array
    {
        return [];
    }

    public function context(Request $request): ?SamContext
    {
        return null;
    }

    public function suggestions(User $actor, ?SamContext $context): array
    {
        return [];
    }

    public function proposals(): ?SamProposalPresenter
    {
        return null;
    }
}
