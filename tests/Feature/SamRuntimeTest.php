<?php

use App\Ai\Sam\SamAgent;
use App\Ai\Sam\SamContext;
use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\SamRuntime;
use App\Models\AiChatMessage;
use App\Models\AiConversation;
use App\Models\AiUsageEvent;
use App\Models\User;
use Tests\Fixtures\Sam\FakeSamContributor;

beforeEach(function (): void {
    resetSamFixtures();
});

function registerFakeSam(): void
{
    app(SamRegistry::class)->register('fake', FakeSamContributor::class);
}

it('is disabled by default', function (): void {
    grantSamPermission();

    $user = User::factory()->create();

    expect(app(SamRuntime::class)->enabled())->toBeFalse()
        ->and(app(SamRuntime::class)->send($user, 'Hola')->error)->toBe('disabled');
});

it('denies an identity without the surface permission', function (): void {
    configureSam();

    $user = User::factory()->create();

    expect(app(SamRuntime::class)->canUse($user))->toBeFalse()
        ->and(app(SamRuntime::class)->send($user, 'Hola')->error)->toBe('forbidden');
});

it('is not ready without a model that supports tools', function (): void {
    configureSam();
    grantSamPermission();
    config()->set('lnx_ai.allowed_models', [
        'test/sam-model' => [
            'tools' => false,
            'streaming' => true,
            'context' => 128000,
        ],
    ]);

    $user = User::factory()->create();

    expect(app(SamRuntime::class)->ready())->toBeFalse()
        ->and(app(SamRuntime::class)->send($user, 'Hola')->error)->toBe('not_configured');
});

it('stores the conversation with profile sam and the correlation id', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSam();
    SamAgent::fake(['Hola, soy Fake Sam. ¿Qué necesitas?']);

    $user = User::factory()->create();

    $reply = app(SamRuntime::class)->send(
        $user,
        'Hola',
        new SamContext('fake', 'contact', '1', 'Ada', 'contacts.show', ['status' => 'active']),
    );

    expect($reply->succeeded())->toBeTrue();

    $conversation = AiConversation::query()
        ->where('user_id', $user->getKey())
        ->where('profile', 'sam')
        ->first();

    expect($conversation)->not->toBeNull();

    $messages = $conversation->messages()->orderBy('created_at')->get();

    expect($messages)->toHaveCount(2)
        ->and($messages->first()->role)->toBe('user')
        ->and($messages->first()->correlation_id)->toBe($reply->correlationId)
        ->and($messages->last()->role)->toBe('assistant')
        ->and($messages->last()->correlation_id)->toBe($reply->correlationId)
        ->and($messages->last()->content)->toContain('Fake Sam');
});

it('rejects empty and oversized input', function (): void {
    configureSam();
    grantSamPermission();
    config()->set('lnx_sam.max_input_chars', 10);

    $runtime = app(SamRuntime::class);
    $user = User::factory()->create();

    expect($runtime->send($user, '   ')->error)->toBe('empty_message')
        ->and($runtime->send($user, str_repeat('a', 11))->error)->toBe('too_long');
});

it('isolates the conversation by actor', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSam();
    SamAgent::fake(['ok']);

    $first = User::factory()->create();
    $second = User::factory()->create();

    $runtime = app(SamRuntime::class);
    $runtime->send($first, 'Hola A');
    $runtime->send($second, 'Hola B');

    expect($runtime->messages($first)->first()->content)->toBe('Hola A')
        ->and($runtime->messages($second)->first()->content)->toBe('Hola B')
        ->and(AiConversation::query()->where('profile', 'sam')->count())->toBe(2);
});

it('creates one conversation per actor even after several turns', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSam();
    SamAgent::fake(['ok', 'ok again']);

    $user = User::factory()->create();

    $runtime = app(SamRuntime::class);
    $runtime->send($user, 'Primero');
    $runtime->send($user, 'Segundo');

    expect(AiConversation::query()->where('profile', 'sam')->count())->toBe(1)
        ->and($runtime->messages($user))->toHaveCount(4);
});

it('returns the suggestions of the contributors for authorized identities', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSam();

    $user = User::factory()->create();

    expect(app(SamRuntime::class)->suggestions($user))->toBe(['Muéstrame los registros de prueba']);
});

it('aggregates the screen context declared by the modules', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSam();

    FakeSamContributor::$context = new SamContext('fake', 'contact', '42', 'Ada');

    $context = app(SamRuntime::class)->contextFor(request());

    expect($context)->toBeInstanceOf(SamContext::class)
        ->and($context->id)->toBe('42');
});

it('works without any contributor', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['Hola, ¿en qué te ayudo?']);

    $reply = app(SamRuntime::class)->send(User::factory()->create(), 'Hola');

    expect($reply->succeeded())->toBeTrue()
        ->and(AiChatMessage::query()->count())->toBe(2);
});

it('records the turn under the sam.turn application', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSam();
    SamAgent::fake(['ok']);

    $reply = app(SamRuntime::class)->send(User::factory()->create(), 'Hola');

    $event = AiUsageEvent::query()->where('correlation_id', $reply->correlationId)->first();

    expect($event)->not->toBeNull()
        ->and($event->application)->toBe('sam.turn');
});

it('prunes messages older than the retention period', function (): void {
    configureSam();
    grantSamPermission();
    registerFakeSam();
    SamAgent::fake(['ok']);

    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);
    $conversation = $runtime->conversationFor($user);

    $old = AiChatMessage::create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Muy antiguo',
        'status' => 'completed',
    ]);

    $old->forceFill(['created_at' => now()->subDays(30)])->save();
    config()->set('lnx_sam.retention_days', 7);

    $runtime->send($user, 'Nuevo');

    expect(AiChatMessage::query()->whereKey($old->getKey())->exists())->toBeFalse();
});
