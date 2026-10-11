<?php

namespace Tests\Fixtures\Mcp;

use App\Mcp\Tools\WriteTool;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * A write tool that forgot to declare #[WritesData]. Used to prove the kit
 * verification fails instead of silently exposing an undeclared write.
 */
#[Name('undeclared-write')]
#[Description('A write tool without a declaration; it must fail kit verification.')]
class UndeclaredWriteTool extends WriteTool
{
    protected function authorize(User $user): bool
    {
        return true;
    }

    protected function perform(Request $request): Response|ResponseFactory
    {
        return Response::structured(['ok' => true]);
    }
}
