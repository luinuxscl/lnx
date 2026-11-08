<?php

namespace Tests\Fixtures\Sam;

use App\Models\User;
use Illuminate\Http\Request;
use Luinuxscl\LnxSam\SamBrief;
use Luinuxscl\LnxSam\SamBriefPresenter;
use Luinuxscl\LnxSam\SamContext;
use Luinuxscl\LnxSam\SamContributor;
use Luinuxscl\LnxSam\SamInsight;
use Luinuxscl\LnxSam\SamProposalPresenter;

class FakeSamBriefContributor implements SamBriefPresenter, SamContributor
{
    public static ?SamBrief $brief = null;

    public static ?SamInsight $insight = null;

    public function source(): string
    {
        return 'fake-brief';
    }

    public function label(): string
    {
        return 'Fake brief module';
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

    public function rowContext(User $actor, string $resource, string $id, array $filters = []): ?SamContext
    {
        return null;
    }

    public function signals(User $actor, SamContext $context): array
    {
        return [];
    }

    public function suggestions(User $actor, ?SamContext $context): array
    {
        return [];
    }

    public function proposals(): ?SamProposalPresenter
    {
        return null;
    }

    public function brief(User $actor, SamContext $context): ?SamBrief
    {
        return self::$brief;
    }

    public function insight(User $actor, SamContext $context): ?SamInsight
    {
        return self::$insight;
    }
}
