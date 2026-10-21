<?php

use App\Ai\Agents\ChatAgent;
use App\Ai\AiErrorCategorizer;
use App\Ai\ChatService;
use App\Ai\Tools\ProductHelpSearchTool;
use App\Livewire\Ai\Chat;
use App\Models\AiUsageEvent;
use App\Models\User;
use App\Support\Help\HelpRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function configureChat(string $model = 'test/chat-model', bool $supportsStreaming = true): void
{
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', $model);
    config()->set('lnx_ai.allowed_models', [
        $model => [
            'tools' => true,
            'structured' => false,
            'streaming' => $supportsStreaming,
            'context' => 128000,
        ],
    ]);
    config()->set('ai.providers.openrouter.key', 'test-key');
}

it('requires authentication to access the chat page', function () {
    $this->get(route('chat'))->assertRedirect(route('login'));
});

it('allows authenticated users to view the chat page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('chat'))
        ->assertOk()
        ->assertSee('Chat interactivo');
});

it('enforces strict user isolation for conversations', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $chatService = app(ChatService::class);

    $convA = $chatService->createConversation($userA, 'User A Private Chat');
    $convB = $chatService->createConversation($userB, 'User B Private Chat');

    // User A can list and retrieve their own conversation
    expect($chatService->getConversations($userA))->toHaveCount(1);
    expect($chatService->getConversation($userA, $convA->id)->id)->toBe($convA->id);

    // User A cannot retrieve User B's conversation
    expect(fn () => $chatService->getConversation($userA, $convB->id))
        ->toThrow(ModelNotFoundException::class);

    // User A cannot delete User B's conversation
    expect(fn () => $chatService->deleteConversation($userA, $convB->id))
        ->toThrow(ModelNotFoundException::class);
});

it('rejects models missing streaming capability', function () {
    configureChat('test/no-stream-model', supportsStreaming: false);

    $user = User::factory()->create();
    $chatService = app(ChatService::class);
    $conv = $chatService->createConversation($user, 'Test');

    $result = $chatService->sendMessage($user, $conv, 'Hello');

    expect($result['status'])->toBe('failed')
        ->and($result['error_category'])->toBe(AiErrorCategorizer::MODEL);
});

it('sends messages and streams responses through ChatAgent recording usage', function () {
    configureChat();
    ChatAgent::fake(['Hello! How can I help you?']);

    $user = User::factory()->create();
    $chatService = app(ChatService::class);

    $conv = $chatService->createConversation($user);

    $result = $chatService->sendMessage($user, $conv, 'Hello assistant');

    expect($result['status'])->toBe('completed')
        ->and($result['text'])->toBe('Hello! How can I help you?')
        ->and($conv->fresh()->title)->toBe('Hello assistant');

    ChatAgent::assertPrompted(
        fn ($prompt): bool => $prompt->model === 'test/chat-model'
            && str_contains($prompt->prompt, 'Hello assistant')
    );

    expect(AiUsageEvent::query()
        ->where('application', 'chat')
        ->where('status', AiUsageEvent::STATUS_SUCCESS)
        ->exists())->toBeTrue();
});

it('authorizes and executes ProductHelpSearchTool for authenticated users', function () {
    app(HelpRegistry::class)->register('lnx', [
        'lnx.settings' => [
            'type' => 'article',
            'group' => 'Settings',
            'title' => 'Settings',
            'summary' => 'Manage your account preferences.',
            'body' => 'Open the settings page to update your profile.',
        ],
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    $tool = new ProductHelpSearchTool;
    expect($tool->description())->toContain('Search curated product help');

    $request = new Request(['query' => 'settings']);
    $output = (string) $tool->handle($request);

    expect($output)->toContain('Ajustes')
        ->and($output)->toContain('Manage your account preferences.')
        ->and($output)->toContain('topic=lnx.settings');
});

it('denies ProductHelpSearchTool execution if unauthenticated', function () {
    Auth::logout();

    $tool = new ProductHelpSearchTool;
    $request = new Request(['query' => 'settings']);
    $output = (string) $tool->handle($request);

    expect($output)->toContain('Unauthorized');
});

it('interacts with Livewire Chat component cleanly', function () {
    configureChat();
    ChatAgent::fake(['Response from assistant']);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Chat::class)
        ->call('newConversation')
        ->set('inputPrompt', 'What is lnx?')
        ->call('sendMessage')
        ->assertHasNoErrors()
        ->assertSee('What is lnx?')
        ->assertSee('Response from assistant');
});

it('handles disabled AI state without breaking conversation or duplicating messages', function () {
    config()->set('lnx_ai.enabled', false);

    $user = User::factory()->create();
    $chatService = app(ChatService::class);
    $conv = $chatService->createConversation($user, 'Test');

    $result = $chatService->sendMessage($user, $conv, 'Test message');

    expect($result['status'])->toBe('failed')
        ->and($result['error_category'])->toBe(AiErrorCategorizer::NOT_CONFIGURED);

    expect($conv->messages()->count())->toBe(2);
});
