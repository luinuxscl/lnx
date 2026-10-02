<?php

use App\Livewire\Settings\McpTokens;
use App\Models\McpToken;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

it('renders the MCP access section on the security page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee(__('MCP access tokens'))
        ->assertSee(__('No access tokens yet'));
});

it('creates a token and shows the plain text only once', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test(McpTokens::class)
        ->set('name', 'Reporting agent')
        ->call('createToken')
        ->assertHasNoErrors();

    $plainText = $component->get('plainText');

    expect($plainText)->toStartWith('lnx_mcp_');
    expect(McpToken::query()->firstOrFail()->token_hash)->toBe(hash('sha256', $plainText));
    expect(json_encode(McpToken::query()->firstOrFail()->toArray()))->not->toContain($plainText);

    $component->call('dismissToken')->assertSet('plainText', '');
    expect(McpToken::query()->firstOrFail()->token_hash)->toBe(hash('sha256', $plainText));
});

it('validates the token name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(McpTokens::class)
        ->set('name', '')
        ->call('createToken')
        ->assertHasErrors(['name' => 'required']);
});

it('revokes a token through the interface', function () {
    $user = User::factory()->create();
    $token = McpToken::factory()->for($user)->create(['name' => 'Old agent']);

    Livewire::actingAs($user)->test(McpTokens::class)
        ->call('confirmRevoke', $token->id)
        ->assertSet('showRevokeModal', true)
        ->call('revoke')
        ->assertSet('showRevokeModal', false);

    expect($token->fresh()->isRevoked())->toBeTrue();
});

it('isolates tokens between users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $token = McpToken::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($other)->test(McpTokens::class)->call('confirmRevoke', $token->id))
        ->toThrow(ModelNotFoundException::class);

    expect($token->fresh()->isRevoked())->toBeFalse();
});

it('lists only the tokens owned by the user', function () {
    $user = User::factory()->create();
    McpToken::factory()->for($user)->create(['name' => 'Mine']);
    McpToken::factory()->create(['name' => 'Theirs']);

    Livewire::actingAs($user)->test(McpTokens::class)
        ->assertSee('Mine')
        ->assertDontSee('Theirs');
});
