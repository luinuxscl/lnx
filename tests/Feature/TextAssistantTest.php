<?php

use App\Ai\Agents\TextAssistantAgent;
use App\Enums\Role;
use App\Livewire\Ai\TextAssistant;
use App\Models\AiUsageEvent;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Fixtures\TextAssistantForm;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function textAssistantRoot(): User
{
    $root = User::factory()->create();
    $root->assignRole(Role::Root->value);

    return $root;
}

function configureTextAssistant(string $model = 'test/model'): void
{
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', $model);
    config()->set('lnx_ai.allowed_models', [$model => ['tools' => false, 'structured' => false, 'streaming' => true, 'context' => 128000]]);
    config()->set('ai.providers.openrouter.key', 'test-key');
}

it('forbids guests from using the assistant', function () {
    Livewire::test(TextAssistant::class)->assertForbidden();
});

it('guards the text assistant demo route', function () {
    $this->get(route('admin.ai.text-assistant'))->assertRedirect(route('login'));

    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $this->actingAs($admin)->get(route('admin.ai.text-assistant'))->assertForbidden();

    $this->actingAs(textAssistantRoot())
        ->get(route('admin.ai.text-assistant'))
        ->assertOk()
        ->assertSee('Asistente de texto');
});

it('forbids authenticated users without the required ability', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(TextAssistant::class)
        ->assertForbidden();
});

it('honours a project-defined ability', function () {
    configureTextAssistant();
    Gate::define('use-custom-assistant', fn (User $user): bool => true);

    Livewire::actingAs(User::factory()->create())
        ->test(TextAssistant::class, ['ability' => 'use-custom-assistant'])
        ->assertOk();
});

it('generates a proposal through the gateway and records the application', function () {
    configureTextAssistant();
    TextAssistantAgent::fake(['Improved text']);

    $root = textAssistantRoot();

    $component = Livewire::actingAs($root)
        ->test(TextAssistant::class)
        ->set('text', 'Original text')
        ->call('generate')
        ->assertHasNoErrors()
        ->assertSet('proposal', 'Improved text')
        ->assertSet('text', 'Original text');

    TextAssistantAgent::assertPrompted(
        fn ($prompt): bool => $prompt->model === 'test/model'
            && str_contains($prompt->prompt, 'Original text')
    );

    expect(AiUsageEvent::query()
        ->where('application', 'text_assistant')
        ->where('status', AiUsageEvent::STATUS_SUCCESS)
        ->exists())->toBeTrue();
});

it('applies the proposal to the bound text only when accepted', function () {
    configureTextAssistant();
    TextAssistantAgent::fake(['Improved text']);

    $root = textAssistantRoot();

    Livewire::actingAs($root)
        ->test(TextAssistant::class)
        ->set('text', 'Original text')
        ->call('generate')
        ->call('accept')
        ->assertSet('text', 'Improved text')
        ->assertSet('proposal', null);
});

it('keeps the original text when the proposal is discarded', function () {
    configureTextAssistant();
    TextAssistantAgent::fake(['Improved text']);

    $root = textAssistantRoot();

    Livewire::actingAs($root)
        ->test(TextAssistant::class)
        ->set('text', 'Original text')
        ->call('generate')
        ->call('discard')
        ->assertSet('text', 'Original text')
        ->assertSet('proposal', null);
});

it('invalidates a pending proposal when the user edits the text', function () {
    configureTextAssistant();
    TextAssistantAgent::fake(['Improved text']);

    $root = textAssistantRoot();

    Livewire::actingAs($root)
        ->test(TextAssistant::class)
        ->set('text', 'Original text')
        ->call('generate')
        ->assertSet('proposal', 'Improved text')
        ->set('text', 'Edited later')
        ->assertSet('proposal', null)
        ->assertSet('text', 'Edited later');
});

it('validates input size, action and language on the server', function () {
    configureTextAssistant();
    $root = textAssistantRoot();

    Livewire::actingAs($root)->test(TextAssistant::class)
        ->set('text', '')
        ->call('generate')
        ->assertHasErrors('text');

    Livewire::actingAs($root)->test(TextAssistant::class)
        ->set('action', 'explode')
        ->call('generate')
        ->assertHasErrors('action');

    Livewire::actingAs($root)->test(TextAssistant::class)
        ->set('action', 'translate')
        ->call('generate')
        ->assertHasErrors('language');

    Livewire::actingAs($root)->test(TextAssistant::class)
        ->set('action', 'translate')
        ->set('language', 'fr')
        ->call('generate')
        ->assertHasErrors('language');

    config()->set('lnx_ai.text_assistant.max_input_chars', 10);

    Livewire::actingAs($root)->test(TextAssistant::class)
        ->set('text', str_repeat('a', 11))
        ->call('generate')
        ->assertHasErrors('text');
});

it('does not call the provider and preserves the text when AI is disabled', function () {
    config()->set('lnx_ai.enabled', false);
    TextAssistantAgent::fake(['should not be used']);

    $root = textAssistantRoot();

    Livewire::actingAs($root)
        ->test(TextAssistant::class)
        ->set('text', 'Original text')
        ->call('generate')
        ->assertHasErrors('generation')
        ->assertSet('proposal', null)
        ->assertSet('text', 'Original text');

    TextAssistantAgent::assertNeverPrompted();
});

it('preserves the text when the local quota is exceeded', function () {
    configureTextAssistant();
    config()->set('lnx_ai.limits.user', ['window' => 'hour', 'requests' => 1, 'tokens' => 0]);
    config()->set('lnx_ai.limits.default', ['window' => 'hour', 'requests' => 0, 'tokens' => 0]);
    config()->set('lnx_ai.limits.applications', []);

    TextAssistantAgent::fake(['Improved text', 'Improved again']);

    $root = textAssistantRoot();

    Livewire::actingAs($root)
        ->test(TextAssistant::class)
        ->set('text', 'First')
        ->call('generate')
        ->assertSet('proposal', 'Improved text');

    Livewire::actingAs($root)
        ->test(TextAssistant::class)
        ->set('text', 'Second')
        ->call('generate')
        ->assertHasErrors('generation')
        ->assertSet('proposal', null)
        ->assertSet('text', 'Second');

    TextAssistantAgent::assertPromptedTimes(1);
});

it('renders the proposal as safe text without executing HTML', function () {
    configureTextAssistant();
    TextAssistantAgent::fake(['<script>alert(1)</script>']);

    $root = textAssistantRoot();

    Livewire::actingAs($root)
        ->test(TextAssistant::class)
        ->set('text', 'Original text')
        ->call('generate')
        ->assertSee('<script>alert(1)</script>')
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('binds the assistant to the host component through wire:model', function () {
    configureTextAssistant();

    $root = textAssistantRoot();

    Livewire::actingAs($root)
        ->test(TextAssistantForm::class)
        ->assertSee('x-modelable="$wire.text"', false)
        ->assertSee('wire:model="$parent.description"', false);
});
