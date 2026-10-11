<?php

use App\Enums\Role;
use App\Mcp\McpToolRegistry;
use App\Mcp\Servers\LnxServer;
use App\Mcp\Tools\ListCapabilitiesTool;
use App\Models\McpToolInvocation;
use App\Models\McpWriteOperation;
use App\Models\Setting;
use App\Models\User;
use App\Support\Permissions\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Tests\Fixtures\Mcp\UndeclaredWriteTool;
use Tests\Fixtures\Mcp\UpdateFooterTextTool;

beforeEach(function () {
    app(PermissionRegistry::class)->register('tests', ['tests.write']);
    app(McpToolRegistry::class)->register('tests', [UpdateFooterTextTool::class]);

    $this->seed(RolePermissionSeeder::class);

    Setting::factory()->create(['footer_text' => 'Original footer']);
});

function mcpWriteActor(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    return $user;
}

it('hides write tools while writes are disabled', function () {
    config(['mcp_server.writes.enabled' => false]);
    $writer = mcpWriteActor();

    LnxServer::actingAs($writer)->tools()->assertNotRegistered([UpdateFooterTextTool::class]);

    LnxServer::actingAs($writer)
        ->tool(UpdateFooterTextTool::class, ['footer_text' => 'Any', 'idempotency_key' => 'k'])
        ->assertHasErrors();
});

it('shows a write tool only to identities holding the declared permission', function () {
    config(['mcp_server.writes.enabled' => true]);
    $member = User::factory()->create();

    LnxServer::actingAs(mcpWriteActor())->tools()->assertRegistered([UpdateFooterTextTool::class]);
    LnxServer::actingAs($member)->tools()->assertNotRegistered([UpdateFooterTextTool::class]);
});

it('applies the write, audits the diff and replays idempotent retries', function () {
    config(['mcp_server.writes.enabled' => true]);
    $writer = mcpWriteActor();

    LnxServer::actingAs($writer)->tool(UpdateFooterTextTool::class, [
        'footer_text' => 'New footer',
        'idempotency_key' => 'key-1',
    ])->assertOk();

    expect(Setting::query()->first()->footer_text)->toBe('New footer');

    $operation = McpWriteOperation::query()->sole();
    expect($operation->tool)->toBe('update-footer-text')
        ->and($operation->resource)->toBe('setting')
        ->and($operation->operation)->toBe('update')
        ->and($operation->idempotency_key)->toBe('key-1')
        ->and($operation->status)->toBe(McpWriteOperation::STATUS_SUCCESS)
        ->and($operation->actor_id)->toBe($writer->id)
        ->and($operation->diff)->toBe(['footer_text' => ['old' => 'Original footer', 'new' => 'New footer']]);

    LnxServer::actingAs($writer)->tool(UpdateFooterTextTool::class, [
        'footer_text' => 'Ignored on replay',
        'idempotency_key' => 'key-1',
    ])->assertOk();

    expect(McpWriteOperation::query()->count())->toBe(1)
        ->and(Setting::query()->first()->footer_text)->toBe('New footer')
        ->and(McpToolInvocation::query()->where('status', McpWriteOperation::STATUS_DUPLICATE)->count())->toBe(1);

    LnxServer::actingAs($writer)->tool(UpdateFooterTextTool::class, [
        'footer_text' => 'Third value',
        'idempotency_key' => 'key-2',
    ])->assertOk();

    expect(McpWriteOperation::query()->count())->toBe(2)
        ->and(Setting::query()->first()->footer_text)->toBe('Third value');
});

it('requires an idempotency key for idempotent writes', function () {
    config(['mcp_server.writes.enabled' => true]);

    LnxServer::actingAs(mcpWriteActor())
        ->tool(UpdateFooterTextTool::class, ['footer_text' => 'No key'])
        ->assertHasErrors();

    expect(McpWriteOperation::query()->count())->toBe(0)
        ->and(McpToolInvocation::query()->where('status', McpWriteOperation::STATUS_INVALID)->count())->toBe(1);
});

it('does not store the diff when diff auditing is disabled', function () {
    config(['mcp_server.writes.enabled' => true, 'mcp_server.audit.diff' => false]);

    LnxServer::actingAs(mcpWriteActor())->tool(UpdateFooterTextTool::class, [
        'footer_text' => 'New footer',
        'idempotency_key' => 'key-1',
    ])->assertOk();

    expect(McpWriteOperation::query()->sole()->diff)->toBeNull();
});

it('audits a denied write invocation', function () {
    config(['mcp_server.writes.enabled' => true]);
    $member = User::factory()->create();
    $this->actingAs($member);

    $response = app(UpdateFooterTextTool::class)
        ->handle(new Request(['footer_text' => 'Blocked', 'idempotency_key' => 'k']));

    expect($response)->toBeInstanceOf(Response::class)
        ->and(McpToolInvocation::query()->where('tool', 'update-footer-text')->where('status', McpWriteOperation::STATUS_DENIED)->count())->toBe(1)
        ->and(McpWriteOperation::query()->count())->toBe(0);
});

it('rate limits writes independently from reads', function () {
    config(['mcp_server.writes.enabled' => true, 'mcp_server.writes.rate_limit_per_minute' => 1]);
    $writer = mcpWriteActor();
    RateLimiter::clear('mcp:write:'.$writer->id);

    LnxServer::actingAs($writer)->tool(UpdateFooterTextTool::class, [
        'footer_text' => 'First',
        'idempotency_key' => 'key-1',
    ])->assertOk();

    LnxServer::actingAs($writer)->tool(UpdateFooterTextTool::class, [
        'footer_text' => 'Second',
        'idempotency_key' => 'key-2',
    ])->assertHasErrors();

    expect(McpWriteOperation::query()->count())->toBe(1)
        ->and(Setting::query()->first()->footer_text)->toBe('First');
});

it('lists the declared write capability for the identity that holds the permission', function () {
    config(['mcp_server.writes.enabled' => true]);
    $member = User::factory()->create();

    LnxServer::actingAs(mcpWriteActor())->tool(ListCapabilitiesTool::class)
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->has('capabilities', 3)
                ->where('capabilities.2.name', 'update-footer-text');
        });

    LnxServer::actingAs($member)->tool(ListCapabilitiesTool::class)
        ->assertOk()
        ->assertStructuredContent(function ($json): void {
            $json->has('capabilities', 1)
                ->where('capabilities.0.name', 'list-capabilities');
        });
});

it('fails verification when a write tool does not declare its writes', function () {
    app(McpToolRegistry::class)->register('tests', [UndeclaredWriteTool::class]);

    $this->artisan('mcp:verify-writes')->assertExitCode(1);

    expect(fn () => UndeclaredWriteTool::declaration())
        ->toThrow(InvalidArgumentException::class);
});

it('passes verification when every write tool is declared', function () {
    $this->artisan('mcp:verify-writes')->assertExitCode(0);
});
