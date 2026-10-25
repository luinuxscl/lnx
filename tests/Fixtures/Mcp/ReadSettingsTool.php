<?php

namespace Tests\Fixtures\Mcp;

use App\Enums\Role;
use App\Mcp\Tools\ReadOnlyTool;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Name('read-settings')]
#[Description('Read a fixture value reserved for root identities.')]
class ReadSettingsTool extends ReadOnlyTool
{
    protected function authorize(User $user): bool
    {
        return $user->hasRole(Role::Root->value);
    }

    protected function perform(Request $request): Response|ResponseFactory
    {
        return Response::structured(['fixture' => true]);
    }
}
