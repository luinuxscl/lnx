<?php

use App\Enums\AdministrativeAction;
use App\Enums\Role;
use App\Mcp\Servers\LnxServer;
use App\Mcp\Tools\GetAdministrativeActivityTool;
use App\Mcp\Tools\GetPublicConfigurationTool;
use App\Mcp\Tools\ListCapabilitiesTool;
use App\Mcp\Tools\SearchUsersTool;
use App\Models\AdministrativeActivity;
use App\Models\McpToolInvocation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function mcpToolUser(Role $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

it('discovers only the tools the identity may invoke', function () {
    $root = mcpToolUser(Role::Root);
    $admin = mcpToolUser(Role::Admin);
    $member = User::factory()->create();

    LnxServer::actingAs($root)->tools()
        ->assertRegistered([
            ListCapabilitiesTool::class,
            GetPublicConfigurationTool::class,
            SearchUsersTool::class,
            GetAdministrativeActivityTool::class,
        ]);

    LnxServer::actingAs($admin)->tools()
        ->assertRegistered([ListCapabilitiesTool::class, SearchUsersTool::class])
        ->assertNotRegistered([GetPublicConfigurationTool::class, GetAdministrativeActivityTool::class]);

    LnxServer::actingAs($member)->tools()
        ->assertRegistered([ListCapabilitiesTool::class])
        ->assertNotRegistered([GetPublicConfigurationTool::class, SearchUsersTool::class, GetAdministrativeActivityTool::class]);
});

it('lists the capabilities allowed for the actor', function () {
    $admin = mcpToolUser(Role::Admin);

    $response = LnxServer::actingAs($admin)->tool(ListCapabilitiesTool::class);

    $response->assertOk()->assertStructuredContent(function ($json): void {
        $json->has('capabilities', 2)
            ->where('capabilities.0.name', 'list-capabilities')
            ->where('capabilities.1.name', 'search-users');
    });
});

it('returns only the public configuration whitelist to root', function () {
    $root = mcpToolUser(Role::Root);

    $response = LnxServer::actingAs($root)->tool(GetPublicConfigurationTool::class);

    $response->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->has('application')->has('localization')->has('site')->etc();
        })
        ->assertDontSee('admin_email')
        ->assertDontSee('APP_KEY');
});

it('denies configuration and activity to admins and members', function () {
    $admin = mcpToolUser(Role::Admin);
    $member = User::factory()->create();

    LnxServer::actingAs($admin)->tool(GetPublicConfigurationTool::class)->assertHasErrors();
    LnxServer::actingAs($admin)->tool(GetAdministrativeActivityTool::class)->assertHasErrors();
    LnxServer::actingAs($member)->tool(SearchUsersTool::class)->assertHasErrors();
    LnxServer::actingAs(mcpToolUser(Role::Root))->tool(GetPublicConfigurationTool::class)->assertOk();
});

it('searches users with pagination and isolates results', function () {
    $admin = mcpToolUser(Role::Admin);
    User::factory()->create(['name' => 'Alice Lobos', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Sincola', 'email' => 'bob@example.com']);

    $response = LnxServer::actingAs($admin)->tool(SearchUsersTool::class, ['query' => 'Alice']);

    $response->assertOk()->assertStructuredContent(function ($json): void {
        $json->has('data', 1)
            ->where('data.0.name', 'Alice Lobos')
            ->has('meta')
            ->where('meta.total', 1);
    });
});

it('validates search contracts', function () {
    $admin = mcpToolUser(Role::Admin);

    LnxServer::actingAs($admin)->tool(SearchUsersTool::class, ['status' => 'bogus'])->assertHasErrors();
    LnxServer::actingAs($admin)->tool(SearchUsersTool::class, ['per_page' => 500])->assertHasErrors();
    LnxServer::actingAs($admin)->tool(SearchUsersTool::class, ['page' => 0])->assertHasErrors();
});

it('reads administrative activity for root and validates ranges', function () {
    $root = mcpToolUser(Role::Root);
    AdministrativeActivity::factory()->create(['action' => AdministrativeAction::UserUpdated, 'occurred_at' => '2026-10-01 12:00:00']);

    LnxServer::actingAs($root)->tool(GetAdministrativeActivityTool::class, [
        'from' => '2026-10-01',
        'to' => '2026-10-01',
    ])->assertOk()->assertStructuredContent(function ($json): void {
        $json->has('data', 1)->where('data.0.action', 'user_updated')->etc();
    });

    LnxServer::actingAs($root)->tool(GetAdministrativeActivityTool::class, [
        'from' => '2026-10-02',
        'to' => '2026-10-01',
    ])->assertHasErrors();
});

it('audits invocations with actor, tool, status and correlation', function () {
    $admin = mcpToolUser(Role::Admin);

    LnxServer::actingAs($admin)->tool(SearchUsersTool::class, ['query' => 'SecretName'])->assertOk();
    LnxServer::actingAs($admin)->tool(SearchUsersTool::class, ['status' => 'bogus'])->assertHasErrors();

    $success = McpToolInvocation::query()->where('status', 'success')->firstOrFail();
    expect($success->tool)->toBe('search-users')
        ->and($success->actor_id)->toBe($admin->id)
        ->and($success->context)->not->toHaveKey('query')
        ->and(json_encode($success->toArray()))->not->toContain('SecretName');

    $invalid = McpToolInvocation::query()->where('status', 'invalid')->firstOrFail();
    expect($invalid->tool)->toBe('search-users')
        ->and($invalid->correlation_id)->not->toBeEmpty();
});
