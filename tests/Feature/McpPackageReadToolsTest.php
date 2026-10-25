<?php

use App\Enums\Role;
use App\Mcp\McpToolRegistry;
use App\Mcp\Servers\LnxServer;
use App\Mcp\Tools\ListCapabilitiesTool;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Tests\Fixtures\Mcp\ReadSettingsTool;

beforeEach(function () {
    app(McpToolRegistry::class)->register('tests', [ReadSettingsTool::class]);

    $this->seed(RolePermissionSeeder::class);
});

function mcpPackageReadUser(?Role $role = null): User
{
    $user = User::factory()->create();

    if ($role !== null) {
        $user->assignRole($role->value);
    }

    return $user;
}

it('registers a package read tool only for identities that may invoke it', function () {
    LnxServer::actingAs(mcpPackageReadUser(Role::Root))->tools()
        ->assertRegistered([ReadSettingsTool::class]);

    LnxServer::actingAs(mcpPackageReadUser())->tools()
        ->assertNotRegistered([ReadSettingsTool::class]);
});

it('announces package read tools in the capability discovery', function () {
    $root = mcpPackageReadUser(Role::Root);
    $member = mcpPackageReadUser();

    LnxServer::actingAs($root)->tool(ListCapabilitiesTool::class)
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->where('capabilities', fn ($capabilities): bool => collect($capabilities)->pluck('name')->contains('read-settings'));
        });

    LnxServer::actingAs($member)->tool(ListCapabilitiesTool::class)
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->where('capabilities', fn ($capabilities): bool => ! collect($capabilities)->pluck('name')->contains('read-settings'));
        });
});
