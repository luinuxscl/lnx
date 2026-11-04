<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamContext;
use App\Ai\Sam\SamContributor;
use App\Ai\Sam\SamProposalPresenter;
use App\Models\User;
use Illuminate\Http\Request;

class FakeSamContributor implements SamContributor
{
    public static bool $withPresenter = false;

    public static bool $withPlainTool = false;

    public static ?string $knowledge = 'Dominio falso con contactos y empresas.';

    public static ?SamContext $context = null;

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

    public function suggestions(User $actor, ?SamContext $context): array
    {
        return ['Muéstrame los registros de prueba'];
    }

    public function proposals(): ?SamProposalPresenter
    {
        return self::$withPresenter ? app(FakeProposalPresenter::class) : null;
    }
}
