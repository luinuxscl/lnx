<?php

use App\Ai\Agents\ChatAgent;
use App\Ai\ChatService;
use App\Ai\Knowledge\KnowledgeIndexer;
use App\Ai\Knowledge\KnowledgeSearchService;
use App\Ai\Tools\KnowledgeSearchTool;
use App\Enums\Role;
use App\Livewire\Ai\Chat as ChatComponent;
use App\Livewire\Knowledge\Bases as BasesComponent;
use App\Models\AiAssistant;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function configureKnowledgeBasesAi(): void
{
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', 'test/chat-model');
    config()->set('lnx_ai.allowed_models', [
        'test/chat-model' => ['tools' => true, 'structured' => false, 'streaming' => true, 'context' => 128000],
        'test/alt-model' => ['tools' => true, 'structured' => false, 'streaming' => true, 'context' => 128000],
    ]);
    config()->set('ai.providers.openrouter.key', 'test-key');
    config()->set('lnx_ai.knowledge.enabled', true);
    config()->set('lnx_ai.knowledge.chunk_size', 100);
    config()->set('lnx_ai.knowledge.chunk_overlap', 20);
    config()->set('lnx_ai.knowledge.min_similarity', 0.1);
}

function baseUser(Role $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role->value);

    return $user;
}

function indexedBaseDocument(User $owner, ?KnowledgeBase $base, string $title, string $content): KnowledgeDocument
{
    $document = KnowledgeDocument::create([
        'user_id' => $owner->id,
        'knowledge_base_id' => $base?->id,
        'title' => $title,
        'source_type' => 'text',
        'content' => $content,
        'status' => 'draft',
    ]);

    app(KnowledgeIndexer::class)->index($document);

    return $document;
}

it('hides root-only bases from admins and standard users', function () {
    configureKnowledgeBasesAi();

    $root = baseUser(Role::Root);
    $admin = baseUser(Role::Admin);
    $standard = User::factory()->create();

    $base = KnowledgeBase::factory()->root()->create();
    indexedBaseDocument($root, $base, 'Root Runbook', 'root classified runbook procedures');

    $service = app(KnowledgeSearchService::class);

    expect($service->search($root, 'runbook', [$base->id]))->not->toBeEmpty()
        ->and($service->search($admin, 'runbook', [$base->id]))->toBeEmpty()
        ->and($service->search($standard, 'runbook', [$base->id]))->toBeEmpty()
        ->and($service->usableBases($admin, [$base->id]))->toBeEmpty()
        ->and($service->usableBases($standard, [$base->id]))->toBeEmpty();
});

it('hides admin bases from standard users but not from admins', function () {
    configureKnowledgeBasesAi();

    $admin = baseUser(Role::Admin);
    $standard = User::factory()->create();

    $base = KnowledgeBase::factory()->admin()->create();
    indexedBaseDocument($admin, $base, 'Admin Handbook', 'administrator handbook procedures');

    $service = app(KnowledgeSearchService::class);

    expect($service->search($admin, 'handbook', [$base->id]))->not->toBeEmpty()
        ->and($service->search($standard, 'handbook', [$base->id]))->toBeEmpty();
});

it('ignores base ids the user is not allowed to see when syncing a conversation', function () {
    configureKnowledgeBasesAi();

    $standard = User::factory()->create();
    $base = KnowledgeBase::factory()->root()->create();

    $chatService = app(ChatService::class);
    $conversation = $chatService->createConversation($standard);
    $chatService->syncKnowledgeBases($standard, $conversation, [$base->id]);

    expect($conversation->knowledgeBases()->count())->toBe(0);
});

it('does not inject RAG context or the search tool when the conversation has no bases', function () {
    configureKnowledgeBasesAi();
    ChatAgent::fake(['plain answer']);

    $user = User::factory()->create();
    $base = KnowledgeBase::factory()->create();
    indexedBaseDocument($user, $base, 'Available Doc', 'available knowledge content');

    $chatService = app(ChatService::class);
    $conversation = $chatService->createConversation($user);

    $chatService->sendMessage($user, $conversation, 'anything');

    ChatAgent::assertPrompted(function ($prompt): bool {
        $tools = collect($prompt->agent->tools())->map(fn ($tool): string => $tool::class);

        return ! str_contains($prompt->prompt, '<knowledge_sources>')
            && ! $tools->contains(KnowledgeSearchTool::class);
    });
});

it('limits the chat search to the bases attached to the conversation', function () {
    configureKnowledgeBasesAi();
    ChatAgent::fake(['answer with sources']);

    $user = User::factory()->create();
    $baseA = KnowledgeBase::factory()->create();
    $baseB = KnowledgeBase::factory()->create();
    indexedBaseDocument($user, $baseA, 'Alpha Manual', 'alpha manual content about procedures');
    indexedBaseDocument($user, $baseB, 'Beta Manual', 'beta manual content about procedures');

    $chatService = app(ChatService::class);
    $conversation = $chatService->createConversation($user, knowledgeBaseIds: [$baseA->id]);

    $chatService->sendMessage($user, $conversation, 'procedures');

    ChatAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Alpha Manual')
        && ! str_contains($prompt->prompt, 'Beta Manual'));
});

it('applies the per-conversation system prompt and model', function () {
    configureKnowledgeBasesAi();
    ChatAgent::fake(['answer']);

    $user = User::factory()->create();
    $chatService = app(ChatService::class);
    $conversation = $chatService->createConversation($user);
    $conversation->update([
        'system_prompt' => 'Always answer as a concise technical writer.',
        'model' => 'test/alt-model',
    ]);

    $chatService->sendMessage($user, $conversation, 'hello');

    ChatAgent::assertPrompted(fn ($prompt): bool => str_contains((string) $prompt->agent->instructions(), 'concise technical writer')
        && $prompt->model === 'test/alt-model');
});

it('inherits prompt, bases and model from an assistant preset', function () {
    configureKnowledgeBasesAi();
    ChatAgent::fake(['answer']);

    $user = User::factory()->create();
    $base = KnowledgeBase::factory()->create();
    indexedBaseDocument($user, $base, 'Preset Manual', 'preset manual content');

    $assistant = AiAssistant::factory()->create([
        'system_prompt' => 'You are the operations preset.',
        'model' => 'test/alt-model',
    ]);
    $assistant->knowledgeBases()->attach($base->id);

    $chatService = app(ChatService::class);
    $conversation = $chatService->createConversation($user, assistantId: $assistant->id);

    $chatService->sendMessage($user, $conversation, 'content');

    ChatAgent::assertPrompted(fn ($prompt): bool => str_contains((string) $prompt->agent->instructions(), 'operations preset')
        && $prompt->model === 'test/alt-model'
        && str_contains($prompt->prompt, 'Preset Manual'));
});

it('prevents standard users from setting the conversation prompt or model', function () {
    configureKnowledgeBasesAi();

    $standard = User::factory()->create();
    $chatService = app(ChatService::class);
    $conversation = $chatService->createConversation($standard);

    Livewire::actingAs($standard)
        ->test(ChatComponent::class)
        ->set('systemPrompt', 'malicious instructions')
        ->set('model', 'test/alt-model')
        ->call('updateConversationConfig')
        ->assertSet('errorMessage', __('You are not allowed to change the prompt or model.'));

    expect($conversation->fresh()->system_prompt)->toBeNull()
        ->and($conversation->fresh()->model)->toBeNull();
});

it('restricts knowledge base management to root and admin', function () {
    $admin = baseUser(Role::Admin);
    $standard = User::factory()->create();

    $this->actingAs($standard)->get(route('knowledge.bases'))->assertForbidden();
    $this->actingAs($admin)->get(route('knowledge.bases'))->assertOk();
});

it('lets an admin create a base but not one with the root audience', function () {
    $admin = baseUser(Role::Admin);

    Livewire::actingAs($admin)
        ->test(BasesComponent::class)
        ->call('openCreateModal')
        ->set('name', 'Operations Manual')
        ->set('audience', 'admin')
        ->call('save')
        ->assertHasNoErrors();

    expect(KnowledgeBase::where('slug', 'operations-manual')->exists())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(BasesComponent::class)
        ->call('openCreateModal')
        ->set('name', 'Forbidden Root Base')
        ->set('audience', 'root')
        ->call('save')
        ->assertHasErrors('audience');
});
