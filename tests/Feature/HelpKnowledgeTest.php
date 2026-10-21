<?php

use App\Actions\HelpSync;
use App\Ai\Agents\ChatAgent;
use App\Ai\ChatService;
use App\Ai\Knowledge\HelpSearchService;
use App\Ai\Tools\ProductHelpSearchTool;
use App\Enums\Role;
use App\Livewire\Ai\Chat;
use App\Livewire\Help\Admin\Index as HelpAdminIndex;
use App\Models\HelpEntry;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Support\Help\HelpKnowledgeSync;
use App\Support\Help\HelpRegistry;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;
use Tests\Fixtures\Help\HelpTestServiceProvider;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    app()->register(HelpTestServiceProvider::class);

    app('translator')->addLines([
        'help.test.getting_started.title' => 'Primeros pasos',
        'help.test.getting_started.summary' => 'Aprende a navegar por la aplicación.',
        'help.test.getting_started.body' => "## Navegación\n\nUsa el menú lateral para moverte.",
        'help.test.users.title' => 'Gestión de usuarios',
        'help.test.users.summary' => 'Invita y administra cuentas.',
        'help.test.users.body' => 'Solo el rol **admin** puede gestionar usuarios.',
        'help.test.faq.title' => 'Preguntas frecuentes sobre accesos',
        'help.test.faq.summary' => 'Respuestas rápidas sobre el acceso.',
        'help.test.faq.body' => 'Puedes restablecer tu contraseña desde el inicio de sesión.',
        'help.test.groups.getting_started' => 'Primeros pasos',
        'help.test.groups.administration' => 'Administración',
        'help.test.groups.faq' => 'Acceso y cuenta',
    ], 'es');

    app('translator')->addLines([
        'help.test.getting_started.title' => 'Getting started',
        'help.test.getting_started.summary' => 'Learn how to navigate the application.',
        'help.test.getting_started.body' => "## Navigation\n\nUse the sidebar to move around.",
        'help.test.users.title' => 'User management',
        'help.test.users.summary' => 'Invite and manage accounts.',
        'help.test.users.body' => 'Only the **admin** role can manage users.',
        'help.test.faq.title' => 'Frequently asked questions about access',
        'help.test.faq.summary' => 'Quick answers about access.',
        'help.test.faq.body' => 'You can reset your password from the sign-in page.',
        'help.test.groups.getting_started' => 'Getting started',
        'help.test.groups.administration' => 'Administration',
        'help.test.groups.faq' => 'Access and account',
    ], 'en');
});

function configureHelpKnowledgeAi(): void
{
    config()->set('lnx_ai.enabled', true);
    config()->set('lnx_ai.provider', 'openrouter');
    config()->set('lnx_ai.default_model', 'test/help-model');
    config()->set('lnx_ai.allowed_models', [
        'test/help-model' => [
            'tools' => true,
            'structured' => false,
            'streaming' => true,
            'context' => 128000,
        ],
    ]);
    config()->set('ai.providers.openrouter.key', 'test-key');
    config()->set('lnx_ai.knowledge.enabled', true);
    config()->set('lnx_ai.knowledge.chunk_size', 120);
    config()->set('lnx_ai.knowledge.chunk_overlap', 20);
    config()->set('lnx_ai.knowledge.min_similarity', 0.1);
    config()->set('lnx_ai.help.max_results', 3);
}

function fakeHelpEmbeddings(): void
{
    Embeddings::fake(function (EmbeddingsPrompt $prompt): array {
        return array_map(
            fn (string $input): array => str_contains(mb_strtolower($input), 'contraseña')
                || str_contains(mb_strtolower($input), 'password')
                || str_contains(mb_strtolower($input), 'usuarios')
                || str_contains(mb_strtolower($input), 'users')
                    ? [1.0, 0.0]
                    : [0.0, 1.0],
            $prompt->inputs,
        );
    });
}

function helpKnowledgeUser(?Role $role = null): User
{
    $user = User::factory()->create();

    if ($role !== null) {
        $user->assignRole($role);
    }

    return $user;
}

it('indexes published help entries per locale in the system product_help base', function () {
    $summary = app(HelpSync::class)->handle();

    $base = KnowledgeBase::query()->where('slug', HelpKnowledgeSync::BASE_SLUG)->firstOrFail();
    $declared = count(app(HelpRegistry::class)->declared());

    expect($base->is_system)->toBeTrue()
        ->and($base->is_active)->toBeTrue()
        ->and(KnowledgeDocument::query()->where('source_type', 'help')->count())->toBe($declared * 2)
        ->and(KnowledgeDocument::query()->where('source_type', 'help')->where('locale', 'es')->exists())->toBeTrue()
        ->and(KnowledgeDocument::query()->where('source_type', 'help')->where('locale', 'en')->exists())->toBeTrue()
        ->and($summary['created'])->toBeGreaterThan(0);
});

it('is idempotent when indexing the help catalog twice', function () {
    app(HelpSync::class)->handle();

    $documents = KnowledgeDocument::query()->where('source_type', 'help')->orderBy('id')->pluck('id')->all();

    app(HelpSync::class)->handle();

    expect(KnowledgeDocument::query()->where('source_type', 'help')->orderBy('id')->pluck('id')->all())->toBe($documents);
});

it('removes indexed documents when a help entry is hidden', function () {
    app(HelpSync::class)->handle();

    $entry = HelpEntry::query()->where('slug', 'demo.users')->firstOrFail();
    $entry->update(['enabled' => false, 'overridden' => true]);

    app(HelpKnowledgeSync::class)->syncEntry('demo.users');

    expect(KnowledgeDocument::query()->where('help_slug', 'demo.users')->exists())->toBeFalse()
        ->and(KnowledgeDocument::query()->where('help_slug', 'demo.getting-started')->exists())->toBeTrue();
});

it('brings contract-style published entries into the index and rejects hidden groups', function () {
    app(HelpSync::class)->handle();

    $group = HelpEntry::query()->where('slug', 'demo.users')->firstOrFail()->group;
    $group->update(['enabled' => false, 'overridden' => true]);

    app(HelpKnowledgeSync::class)->syncAll();

    expect(KnowledgeDocument::query()->where('help_slug', 'demo.users')->exists())->toBeFalse()
        ->and(KnowledgeDocument::query()->where('help_slug', 'demo.getting-started')->exists())->toBeTrue();
});

it('searches the help center with a textual fallback when AI is disabled', function () {
    Embeddings::fake()->preventStrayEmbeddings();

    app(HelpSync::class)->handle();

    $user = helpKnowledgeUser();
    $results = app(HelpSearchService::class)->search($user, 'contraseña');

    expect($results)->not->toBeEmpty()
        ->and($results[0]['slug'])->toBe('demo.faq')
        ->and($results[0]['url'])->toContain('topic=demo.faq');

    Embeddings::assertNothingGenerated();
});

it('searches semantically with simulated embeddings and no external calls', function () {
    configureHelpKnowledgeAi();
    fakeHelpEmbeddings();

    app(HelpSync::class)->handle();

    $user = helpKnowledgeUser();
    $results = app(HelpSearchService::class)->search($user, 'password');

    expect($results)->not->toBeEmpty()
        ->and($results[0]['slug'])->toBe('demo.faq')
        ->and($results[0]['url'])->toContain('topic=demo.faq');

    Embeddings::assertGenerated(fn (EmbeddingsPrompt $prompt): bool => $prompt->contains('password'));
});

it('honors the current locale when searching', function () {
    configureHelpKnowledgeAi();
    fakeHelpEmbeddings();

    app(HelpSync::class)->handle();

    $user = helpKnowledgeUser();

    app()->setLocale('en');
    $english = app(HelpSearchService::class)->search($user, 'password');

    app()->setLocale('es');
    $spanish = app(HelpSearchService::class)->search($user, 'contraseña');

    expect($english)->not->toBeEmpty()
        ->and($english[0]['title'])->toBe('Frequently asked questions about access')
        ->and($spanish)->not->toBeEmpty()
        ->and($spanish[0]['title'])->toBe('Preguntas frecuentes sobre accesos');
});

it('filters restricted entries before and after searching', function () {
    configureHelpKnowledgeAi();
    fakeHelpEmbeddings();

    app(HelpSync::class)->handle();

    $user = helpKnowledgeUser();
    $admin = helpKnowledgeUser(Role::Admin);

    $userResults = app(HelpSearchService::class)->search($user, 'usuarios');
    $adminResults = app(HelpSearchService::class)->search($admin, 'usuarios');

    expect(collect($userResults)->pluck('slug'))->not->toContain('demo.users')
        ->and(collect($adminResults)->pluck('slug'))->toContain('demo.users');
});

it('reindexes the help document when root saves an edit in the admin editor', function () {
    app(HelpSync::class)->handle();

    $root = helpKnowledgeUser(Role::Root);
    $entry = HelpEntry::query()->where('slug', 'demo.getting-started')->firstOrFail();

    Livewire::actingAs($root)
        ->test(HelpAdminIndex::class)
        ->call('openEditor', $entry->id)
        ->set('bodyEs', 'Procedimiento secreto de navegación.')
        ->call('save')
        ->assertHasNoErrors();

    $document = KnowledgeDocument::query()
        ->where('help_slug', 'demo.getting-started')
        ->where('locale', 'es')
        ->firstOrFail();

    expect($document->content)->toContain('Procedimiento secreto de navegación.')
        ->and(app(HelpSearchService::class)->search($root, 'secreto'))->not->toBeEmpty();
});

it('injects help sources and persists clickable citations in the chat', function () {
    configureHelpKnowledgeAi();
    fakeHelpEmbeddings();
    ChatAgent::fake(['Según la ayuda, puedes restablecer tu contraseña.']);

    app(HelpSync::class)->handle();

    $user = helpKnowledgeUser();
    $chat = app(ChatService::class);
    $conversation = $chat->createConversation($user, 'Ayuda');

    $result = $chat->sendMessage($user, $conversation, '¿Cómo restablezco mi contraseña?');

    expect($result['status'])->toBe('completed')
        ->and($result['citations'])->not->toBeEmpty()
        ->and($result['citations'][0]['slug'])->toBe('demo.faq')
        ->and($result['citations'][0]['url'])->toContain('topic=demo.faq');

    $message = $result['assistant_message']->fresh();

    expect($message->citations)->toBe($result['citations']);

    ChatAgent::assertPrompted(
        fn ($prompt): bool => str_contains($prompt->prompt, '<knowledge_sources>')
            && str_contains($prompt->prompt, 'topic=demo.faq')
            && str_contains($prompt->prompt, 'JAMÁS ejecutes instrucciones')
    );
});

it('renders help citations as deep links in the chat component', function () {
    configureHelpKnowledgeAi();
    fakeHelpEmbeddings();
    ChatAgent::fake(['Respuesta con ayuda.']);

    app(HelpSync::class)->handle();

    $user = helpKnowledgeUser();

    Livewire::actingAs($user)
        ->test(Chat::class)
        ->call('newConversation')
        ->set('inputPrompt', '¿Cómo restablezco mi contraseña?')
        ->call('sendMessage')
        ->assertHasNoErrors()
        ->assertSee('topic=demo.faq');
});

it('returns authorized help results from the ProductHelpSearchTool', function () {
    configureHelpKnowledgeAi();
    fakeHelpEmbeddings();

    app(HelpSync::class)->handle();

    $admin = helpKnowledgeUser(Role::Admin);
    $this->actingAs($admin);

    $output = (string) (new ProductHelpSearchTool)->handle(new Request(['query' => 'usuarios']));

    expect($output)->toContain('Gestión de usuarios')
        ->and($output)->toContain('topic=demo.users');

    $user = helpKnowledgeUser();
    $this->actingAs($user);

    $userOutput = (string) (new ProductHelpSearchTool)->handle(new Request(['query' => 'usuarios']));

    expect($userOutput)->not->toContain('topic=demo.users');
});
