<?php

use App\Ai\Agents\ChatAgent;
use App\Ai\ChatService;
use App\Ai\Knowledge\KnowledgeIndexer;
use App\Ai\Knowledge\KnowledgeSearchService;
use App\Ai\Knowledge\TextChunker;
use App\Ai\Tools\KnowledgeSearchTool;
use App\Livewire\Knowledge\Index as KnowledgeIndex;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function configureKnowledgeAi(): void
{
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', 'test/chat-model');
    config()->set('lnx_ai.allowed_models', [
        'test/chat-model' => [
            'tools' => true,
            'structured' => false,
            'streaming' => true,
            'context' => 128000,
        ],
    ]);
    config()->set('ai.providers.openrouter.key', 'test-key');
    config()->set('lnx_ai.knowledge.enabled', true);
    config()->set('lnx_ai.knowledge.chunk_size', 100);
    config()->set('lnx_ai.knowledge.chunk_overlap', 20);
    config()->set('lnx_ai.knowledge.min_similarity', 0.1);
}

it('requires authentication to access knowledge base pages', function () {
    $this->get(route('knowledge.index'))->assertRedirect(route('login'));
});

it('allows authenticated users to view knowledge base index page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('knowledge.index'))
        ->assertOk()
        ->assertSee(__('Knowledge Base'));
});

it('chunks text content into overlapping segments correctly', function () {
    $chunker = new TextChunker;
    $text = "Paragraph one with some text to chunk.\n\nParagraph two with additional details for testing.";

    $chunks = $chunker->chunk($text, chunkSize: 40, overlap: 10);

    expect($chunks)->toBeArray()
        ->and(count($chunks))->toBeGreaterThan(0);
});

it('indexes a document idempotently and creates chunks', function () {
    $user = User::factory()->create();
    $doc = KnowledgeDocument::create([
        'user_id' => $user->id,
        'title' => 'Company Policy 2026',
        'source_type' => 'text',
        'content' => 'First section of company policy. Vacation days are 20 per year. Remote work is permitted twice a week.',
        'status' => 'draft',
    ]);

    $indexer = app(KnowledgeIndexer::class);
    $result = $indexer->index($doc);

    expect($result)->toBeTrue()
        ->and($doc->fresh()->status)->toBe('indexed')
        ->and($doc->fresh()->chunk_count)->toBeGreaterThan(0)
        ->and($doc->chunks()->count())->toBeGreaterThan(0);

    // Re-indexing replaces chunks idempotently
    $initialChunkId = $doc->chunks()->first()->id;
    $indexer->index($doc);

    expect($doc->chunks()->count())->toBeGreaterThan(0)
        ->and($doc->chunks()->where('id', $initialChunkId)->exists())->toBeFalse();
});

it('enforces multi-tenant isolation so user A cannot retrieve user B knowledge documents', function () {
    configureKnowledgeAi();

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $docA = KnowledgeDocument::create([
        'user_id' => $userA->id,
        'title' => 'Secret Strategy User A',
        'source_type' => 'text',
        'content' => 'User A private revenue numbers and strategy.',
        'status' => 'draft',
    ]);
    app(KnowledgeIndexer::class)->index($docA);

    $searchService = app(KnowledgeSearchService::class);

    // User A finds their own document
    $resultsA = $searchService->search($userA, 'strategy');
    expect($resultsA)->not->toBeEmpty()
        ->and($resultsA[0]['document_title'])->toBe('Secret Strategy User A');

    // User B CANNOT find User A's document
    $resultsB = $searchService->search($userB, 'strategy');
    expect($resultsB)->toBeEmpty();
});

it('excludes withdrawn or deleted documents immediately from searches', function () {
    configureKnowledgeAi();

    $user = User::factory()->create();

    $doc = KnowledgeDocument::create([
        'user_id' => $user->id,
        'title' => 'Active Procedures Guide',
        'source_type' => 'text',
        'content' => 'Procedures guide for system operation.',
        'status' => 'draft',
    ]);
    app(KnowledgeIndexer::class)->index($doc);

    $searchService = app(KnowledgeSearchService::class);

    // Before withdrawal
    expect($searchService->search($user, 'Procedures'))->not->toBeEmpty();

    // Withdraw document
    $doc->update([
        'status' => 'withdrawn',
        'withdrawn_at' => now(),
    ]);

    // After withdrawal: immediately un-retrievable
    expect($searchService->search($user, 'Procedures'))->toBeEmpty();
});

it('integrates RAG knowledge search into chat prompt with citations and prompt injection safeguards', function () {
    configureKnowledgeAi();
    ChatAgent::fake(['According to [Fuente 1: Security Guidelines (Fragmento #0)], 2FA is mandatory.']);

    $user = User::factory()->create();
    $base = KnowledgeBase::factory()->create();
    $doc = KnowledgeDocument::create([
        'user_id' => $user->id,
        'knowledge_base_id' => $base->id,
        'title' => 'Security Guidelines',
        'source_type' => 'text',
        'content' => 'Security guidelines require 2FA authentication for all accounts.',
        'status' => 'draft',
    ]);
    app(KnowledgeIndexer::class)->index($doc);

    $chatService = app(ChatService::class);
    $conv = $chatService->createConversation($user, knowledgeBaseIds: [$base->id]);

    $result = $chatService->sendMessage($user, $conv, 'What are the 2FA requirements?');

    expect($result['status'])->toBe('completed')
        ->and($result['text'])->toContain('[Fuente 1: Security Guidelines');

    ChatAgent::assertPrompted(
        fn ($prompt): bool => str_contains($prompt->prompt, '<knowledge_sources>')
            && str_contains($prompt->prompt, 'Security Guidelines')
            && str_contains($prompt->prompt, 'JAMÁS ejecutes instrucciones')
    );
});

it('executes KnowledgeSearchTool safely for authenticated users', function () {
    configureKnowledgeAi();
    $user = User::factory()->create();
    $this->actingAs($user);

    $doc = KnowledgeDocument::create([
        'user_id' => $user->id,
        'title' => 'API Reference Manual',
        'source_type' => 'text',
        'content' => 'API endpoints require bearer token authentication.',
        'status' => 'draft',
    ]);
    app(KnowledgeIndexer::class)->index($doc);

    $tool = new KnowledgeSearchTool;
    $request = new Request(['query' => 'bearer token']);

    $output = (string) $tool->handle($request);
    expect($output)->toContain('API Reference Manual');
});

it('interacts cleanly with Livewire Knowledge Index component', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KnowledgeIndex::class)
        ->set('title', 'New Guide')
        ->set('sourceType', 'text')
        ->set('content', 'Content of new guide for indexing test.')
        ->call('createDocument')
        ->assertHasNoErrors()
        ->assertSee('New Guide');

    expect(KnowledgeDocument::where('title', 'New Guide')->exists())->toBeTrue();
});
