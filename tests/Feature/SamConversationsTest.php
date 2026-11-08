<?php

use App\Models\AiChatMessage;
use App\Models\AiConversation;
use App\Models\McpToolInvocation;
use App\Models\User;
use Luinuxscl\LnxSam\SamAgent;
use Luinuxscl\LnxSam\SamContext;
use Luinuxscl\LnxSam\SamRuntime;

beforeEach(function (): void {
    resetSamFixtures();
});

it('keeps several conversations per actor and lists them newest first', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['Primera respuesta.', 'Segunda respuesta.']);

    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);

    $first = $runtime->startConversation($user);
    $second = $runtime->startConversation($user);

    $runtime->send($user, 'Primera conversación', null, $first);
    $runtime->send($user, 'Segunda conversación', null, $second);

    $conversations = $runtime->conversations($user);

    expect($conversations)->toHaveCount(2)
        ->and($conversations->first()->is($second))->toBeTrue()
        ->and($conversations->first()->messages_count)->toBe(2)
        ->and($conversations->first()->latestMessage?->role)->toBe('assistant')
        ->and($runtime->conversations(User::factory()->create()))->toHaveCount(0);
});

it('keeps conversationFor as the most recent thread', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['ok']);

    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);

    $older = $runtime->startConversation($user);
    $newer = $runtime->startConversation($user);

    expect($runtime->conversationFor($user)->is($newer))->toBeTrue()
        ->and($runtime->findConversation($user)?->is($newer))->toBeTrue()
        ->and($older->is($newer))->toBeFalse();
});

it('derives a deterministic title from the first message only', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['ok', 'ok']);

    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);
    $conversation = $runtime->startConversation($user);

    expect($conversation->title)->toBeNull();

    $runtime->send($user, "  Necesito   revisar\n\nlos contactos sin propietario  ", null, $conversation);

    expect($conversation->fresh()->title)->toBe('Necesito revisar los contactos sin propietario');

    $runtime->send($user, 'Otra cosa completamente distinta', null, $conversation);

    expect($conversation->fresh()->title)->toBe('Necesito revisar los contactos sin propietario');
});

it('bounds the title to the configured characters', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['ok']);

    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);
    $conversation = $runtime->startConversation($user);

    $runtime->send($user, str_repeat('contacto ', 30), null, $conversation);

    $title = (string) $conversation->fresh()->title;

    expect(mb_strlen($title))->toBeLessThanOrEqual(61)
        ->and($title)->toEndWith('…');
});

it('stores the sanitized context with the message of the actor', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['ok']);

    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);

    $context = new SamContext(
        'crm',
        'contact',
        '42',
        'Ada Lovelace',
        'contacts.show',
        ['status' => 'active', 'page' => 2, 'nested' => ['secret' => true]],
    );

    $runtime->send($user, 'Hola', $context);

    $messages = $runtime->messages($user);

    expect($messages)->toHaveCount(2)
        ->and($messages->first()->context)->toBe([
            'source' => 'crm',
            'resource' => 'contact',
            'id' => '42',
            'label' => 'Ada Lovelace',
            'route' => 'contacts.show',
            'filters' => ['status' => 'active', 'page' => 2],
        ])
        ->and($messages->last()->context)->toBeNull();
});

it('sends to the selected conversation without touching the others', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['ok']);

    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);

    $first = $runtime->startConversation($user);
    $second = $runtime->startConversation($user);

    $runtime->send($user, 'En el primero', null, $first);

    expect($runtime->messages($user, $first))->toHaveCount(2)
        ->and($runtime->messages($user, $second))->toHaveCount(0);
});

it('rejects a conversation of another actor', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['ok']);

    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $runtime = app(SamRuntime::class);
    $conversation = $runtime->startConversation($owner);

    $reply = $runtime->send($intruder, 'Hola', null, $conversation);

    expect($reply->succeeded())->toBeFalse()
        ->and($reply->error)->toBe('not_found')
        ->and($runtime->findForActor($intruder, (string) $conversation->getKey()))->toBeNull()
        ->and($runtime->messages($intruder, $conversation))->toHaveCount(0)
        ->and(AiChatMessage::query()->where('conversation_id', $conversation->id)->count())->toBe(0);
});

it('deletes one conversation with its messages and keeps the rest', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['Primera respuesta.', 'Segunda respuesta.']);

    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);

    $kept = $runtime->startConversation($user);
    $deleted = $runtime->startConversation($user);

    $runtime->send($user, 'Primero', null, $kept);
    $runtime->send($user, 'Segundo', null, $deleted);

    expect($runtime->deleteConversation($user, (string) $deleted->getKey()))->toBeTrue()
        ->and(AiConversation::query()->whereKey($deleted->getKey())->exists())->toBeFalse()
        ->and(AiChatMessage::query()->where('conversation_id', $deleted->getKey())->count())->toBe(0)
        ->and($runtime->conversations($user)->pluck('id')->all())->toBe([$kept->getKey()])
        ->and($runtime->messages($user, $kept))->toHaveCount(2);
});

it('refuses to delete a conversation of another actor', function (): void {
    configureSam();
    grantSamPermission();

    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $runtime = app(SamRuntime::class);
    $conversation = $runtime->startConversation($owner);

    expect($runtime->deleteConversation($intruder, (string) $conversation->getKey()))->toBeFalse()
        ->and(AiConversation::query()->whereKey($conversation->getKey())->exists())->toBeTrue();
});

it('forgets every conversation of the actor and keeps the audit log', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['ok', 'ok', 'ok']);

    $user = User::factory()->create();
    $other = User::factory()->create();

    $runtime = app(SamRuntime::class);

    $runtime->send($user, 'Uno');
    $runtime->send($user, 'Dos', null, $runtime->startConversation($user));
    $runtime->send($other, 'De otra identidad');

    $audit = McpToolInvocation::factory()->create(['actor_id' => $user->getKey()]);

    expect($runtime->forgetAll($user))->toBe(2)
        ->and($runtime->conversations($user))->toHaveCount(0)
        ->and(AiChatMessage::query()->whereIn('conversation_id', AiConversation::query()->select('id'))->count())->toBe(2)
        ->and($runtime->conversations($other))->toHaveCount(1)
        ->and(McpToolInvocation::query()->whereKey($audit->getKey())->exists())->toBeTrue();

    $forget = McpToolInvocation::query()
        ->where('tool', 'sam_conversations')
        ->where('context->action', 'forget_all')
        ->first();

    expect($forget)->not->toBeNull()
        ->and($forget->context)->toBe([
            'channel' => 'sam',
            'action' => 'forget_all',
            'conversations' => 2,
            'messages' => 4,
        ]);
});

it('creates no conversation when the agent is disabled', function (): void {
    configureSam();
    grantSamPermission();

    config()->set('lnx_sam.enabled', false);

    $user = User::factory()->create();

    expect(app(SamRuntime::class)->send($user, 'Hola')->error)->toBe('disabled')
        ->and(AiConversation::query()->count())->toBe(0)
        ->and(AiChatMessage::query()->count())->toBe(0);
});

it('prunes old messages and empty conversations by retention', function (): void {
    configureSam();
    grantSamPermission();
    SamAgent::fake(['ok']);

    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);

    $runtime->send($user, 'Antiguo');
    $conversation = $runtime->findConversation($user);
    $conversation->messages()->update(['created_at' => now()->subDays(30)]);

    $empty = $runtime->startConversation($user);
    $empty->forceFill(['created_at' => now()->subDays(30), 'updated_at' => now()->subDays(30)])->save();

    $recent = $runtime->startConversation($user);

    config()->set('lnx_sam.retention_days', 7);

    $this->artisan('sam:prune-conversations')->assertSuccessful();

    expect(AiChatMessage::query()->count())->toBe(0)
        ->and(AiConversation::query()->whereKey($conversation->getKey())->exists())->toBeTrue()
        ->and(AiConversation::query()->whereKey($empty->getKey())->exists())->toBeFalse()
        ->and(AiConversation::query()->whereKey($recent->getKey())->exists())->toBeTrue();
});
