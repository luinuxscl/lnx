<?php

use App\Ai\Agents\ConnectionTestAgent;
use App\Enums\Role;
use App\Livewire\Ai\Status;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function aiRoot(): User
{
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    return $root;
}

function configureAi(string $key = 'test-key', string $model = 'test/model'): void
{
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.default_model', $model);
    config()->set('lnx_ai.allowed_models', [$model => ['tools' => false, 'structured' => false, 'streaming' => true, 'context' => 128000]]);
    config()->set('ai.providers.openrouter.key', $key);
}

it('redirects guests from the AI settings page', function () {
    $this->get(route('admin.ai.index'))->assertRedirect(route('login'));
});

it('forbids users without the root role', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.ai.index'))->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $this->actingAs($admin)->get(route('admin.ai.index'))->assertForbidden();
});

it('shows the AI item in the sidebar only for root', function () {
    $root = aiRoot();
    $this->actingAs($root)->get(route('dashboard'))
        ->assertSee(route('admin.ai.index'));

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $this->actingAs($admin)->get(route('dashboard'))
        ->assertDontSee(route('admin.ai.index'));
});

it('shows configuration status and never exposes the API key', function () {
    configureAi(key: 'super-secret-key');

    $this->actingAs(aiRoot())
        ->get(route('admin.ai.index'))
        ->assertOk()
        ->assertSee('openrouter')
        ->assertSee('test/model')
        ->assertSee('Configurada')
        ->assertDontSee('super-secret-key');

    $component = Livewire::actingAs(aiRoot())->test(Status::class);
    expect(json_encode($component->effects))
        ->not->toContain('super-secret-key')
        ->not->toContain('test-key');
});

it('reports not configured without calling the provider when AI is disabled', function () {
    ConnectionTestAgent::fake(['should not be used']);

    Livewire::actingAs(aiRoot())->test(Status::class)
        ->call('testConnection')
        ->assertSet('result.status', 'error')
        ->assertSet('result.category', 'not_configured');

    ConnectionTestAgent::assertNeverPrompted();
});

it('reports not configured when the model is not allowlisted', function () {
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.default_model', 'not/allowed');
    config()->set('lnx_ai.allowed_models', []);
    config()->set('ai.providers.openrouter.key', 'test-key');

    ConnectionTestAgent::fake(['should not be used']);

    Livewire::actingAs(aiRoot())->test(Status::class)
        ->call('testConnection')
        ->assertSet('result.status', 'error')
        ->assertSet('result.category', 'not_configured');

    ConnectionTestAgent::assertNeverPrompted();
});

it('runs the connection test against the configured provider and model', function () {
    configureAi();
    ConnectionTestAgent::fake(['ok']);

    Livewire::actingAs(aiRoot())->test(Status::class)
        ->call('testConnection')
        ->assertSet('result.status', 'success')
        ->assertSet('result.category', 'ok')
        ->assertSet('result.model', 'test/model');

    ConnectionTestAgent::assertPrompted(
        fn ($prompt): bool => $prompt->model === 'test/model'
            && $prompt->provider->name() === 'openrouter'
    );
});

it('does not call the provider when merely opening the page', function () {
    configureAi();
    ConnectionTestAgent::fake(['ok']);

    Livewire::actingAs(aiRoot())->test(Status::class)->assertOk();

    ConnectionTestAgent::assertNeverPrompted();
});

it('rate limits the connection test per root user', function () {
    configureAi();
    config()->set('lnx_ai.connection_test.per_minute', 1);
    $root = aiRoot();
    RateLimiter::clear('ai-connection-test:'.$root->id);
    ConnectionTestAgent::fake(['ok']);

    Livewire::actingAs($root)->test(Status::class)->call('testConnection')->assertHasNoErrors();

    Livewire::actingAs($root)->test(Status::class)->call('testConnection')->assertHasErrors('connection_test');
});
