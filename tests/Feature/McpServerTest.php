<?php

use App\Actions\IssueMcpToken;
use App\Enums\Role;
use App\Models\McpToken;
use App\Models\McpToolInvocation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    config()->set('mcp_server.enabled', true);
    config()->set('mcp_server.rate_limit.per_minute', 60);
});

function mcpServerUser(?Role $role = null): User
{
    $user = User::factory()->create();

    if ($role !== null) {
        $user->assignRole($role->value);
    }

    return $user;
}

function mcpServerToken(User $user): string
{
    return app(IssueMcpToken::class)->handle($user, 'Test token')['plain_text'];
}

function mcpServerPayload(string $method = 'ping'): array
{
    return ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => []];
}

it('stays disabled until explicitly enabled', function () {
    config()->set('mcp_server.enabled', false);

    $this->postJson('/mcp', mcpServerPayload())->assertNotFound();
});

it('rejects requests without a token', function () {
    $this->postJson('/mcp', mcpServerPayload())->assertUnauthorized();
});

it('rejects invalid, revoked and expired tokens', function () {
    $user = mcpServerUser();

    $this->postJson('/mcp', mcpServerPayload(), ['Authorization' => 'Bearer not-a-real-token'])
        ->assertUnauthorized();

    $revoked = app(IssueMcpToken::class)->handle($user, 'Revoked');
    $revoked['token']->forceFill(['revoked_at' => now()])->save();
    $this->postJson('/mcp', mcpServerPayload(), ['Authorization' => 'Bearer '.$revoked['plain_text']])
        ->assertUnauthorized();

    $expired = app(IssueMcpToken::class)->handle($user, 'Expired');
    $expired['token']->forceFill(['expires_at' => now()->subDay()])->save();
    $this->postJson('/mcp', mcpServerPayload(), ['Authorization' => 'Bearer '.$expired['plain_text']])
        ->assertUnauthorized();
});

it('rejects suspended accounts', function () {
    $user = mcpServerUser();
    $user->forceFill(['suspended_at' => now()])->save();
    $plain = mcpServerToken($user);

    $this->postJson('/mcp', mcpServerPayload(), ['Authorization' => 'Bearer '.$plain])
        ->assertForbidden();

    $invocation = McpToolInvocation::query()->firstOrFail();
    expect($invocation->status)->toBe('denied')->and($invocation->actor_id)->toBe($user->id);
});

it('authenticates a valid token and echoes a correlation id', function () {
    $user = mcpServerUser(Role::Admin);
    $plain = mcpServerToken($user);

    $response = $this->postJson('/mcp', mcpServerPayload(), ['Authorization' => 'Bearer '.$plain]);

    $response->assertOk()->assertHeader('X-Correlation-Id');
    expect(auth()->user()?->is($user))->toBeTrue();
    expect(McpToken::query()->firstOrFail()->last_used_at)->not->toBeNull();
});

it('serves a tool call end to end from a connected agent', function () {
    $user = mcpServerUser(Role::Root);
    $plain = mcpServerToken($user);

    $response = $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 7,
        'method' => 'tools/call',
        'params' => ['name' => 'list-capabilities', 'arguments' => []],
    ], ['Authorization' => 'Bearer '.$plain]);

    $response->assertOk();
    expect($response->json('result.content.0.text'))->toContain('search-users');
});

it('rate limits by identity', function () {
    config()->set('mcp_server.rate_limit.per_minute', 1);
    $user = mcpServerUser();
    $plain = mcpServerToken($user);

    $this->postJson('/mcp', mcpServerPayload(), ['Authorization' => 'Bearer '.$plain])->assertOk();

    $this->postJson('/mcp', mcpServerPayload(), ['Authorization' => 'Bearer '.$plain])
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

it('audits rejected connections without leaking credentials', function () {
    $this->postJson('/mcp', mcpServerPayload(), ['Authorization' => 'Bearer secret-value'])
        ->assertUnauthorized();

    $invocation = McpToolInvocation::query()->firstOrFail();
    expect($invocation->status)->toBe('unauthenticated')
        ->and($invocation->actor_id)->toBeNull()
        ->and(json_encode($invocation->toArray()))->not->toContain('secret-value');
});
