<?php

namespace Tests\Fixtures\Sam;

use App\Models\User;
use Illuminate\Http\Request;
use Luinuxscl\LnxSam\SamContext;
use Luinuxscl\LnxSam\SamContributor;
use Luinuxscl\LnxSam\SamProposalPresenter;
use Luinuxscl\LnxSam\SamSignal;

class FakeSamContributor implements SamContributor
{
    public static bool $withPresenter = false;

    public static bool $withPlainTool = false;

    public static ?string $knowledge = 'Dominio falso con contactos y empresas.';

    public static ?SamContext $context = null;

    public static ?SamContext $rowContext = null;

    /**
     * @var list<SamSignal>
     */
    public static array $signals = [];

    public function source(): string
    {
        return 'fake';
    }

    public function label(): string
    {
        return 'Fake module';
    }

    public function knowledge(User $actor): ?string
    {
        return self::$knowledge;
    }

    public function readTools(User $actor): array
    {
        $tools = [app(FakeReadTool::class)];

        if (self::$withPlainTool) {
            $tools[] = app(PlainTool::class);
        }

        return $tools;
    }

    public function proposeTools(User $actor): array
    {
        return [app(FakeProposeTool::class)];
    }

    public function context(Request $request): ?SamContext
    {
        return self::$context;
    }

    public function rowContext(User $actor, string $resource, string $id, array $filters = []): ?SamContext
    {
        return self::$rowContext;
    }

    public function signals(User $actor, SamContext $context): array
    {
        return self::$signals;
    }

    public function suggestions(User $actor, ?SamContext $context): array
    {
        return ['Muéstrame los registros de prueba'];
    }

    public function proposals(): ?SamProposalPresenter
    {
        return self::$withPresenter ? app(FakeProposalPresenter::class) : null;
    }
}
