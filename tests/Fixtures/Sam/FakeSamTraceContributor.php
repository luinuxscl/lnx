<?php

namespace Tests\Fixtures\Sam;

use App\Models\User;
use Illuminate\Http\Request;
use Luinuxscl\LnxSam\SamContext;
use Luinuxscl\LnxSam\SamContributor;
use Luinuxscl\LnxSam\SamProposalPresenter;
use Luinuxscl\LnxSam\SamTracePresenter;
use Luinuxscl\LnxSam\SamTurnTrace;

class FakeSamTraceContributor implements SamContributor, SamTracePresenter
{
    public static ?SamTurnTrace $trace = null;

    public static bool $denies = false;

    public function source(): string
    {
        return 'fake-trace';
    }

    public function label(): string
    {
        return 'Fake trace module';
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

    public function trace(User $actor, string $correlationId): ?SamTurnTrace
    {
        return self::$denies ? null : self::$trace;
    }
}
