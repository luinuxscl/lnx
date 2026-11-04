<?php

use App\Ai\Sam\SamAgent;
use App\Ai\Sam\SamRedactor;
use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\SamRuntime;
use App\Models\AiChatMessage;
use App\Models\AiConversation;
use App\Models\User;
use Tests\Fixtures\Sam\FakeSamContributor;
use Tests\Fixtures\Sam\FakeSamProfile;

beforeEach(function (): void {
    resetSamFixtures();
    configureSam();
    grantSamPermission();

    app(SamRegistry::class)->register('fake', FakeSamContributor::class);
    app(SamRegistry::class)->registerProfile('fake', FakeSamProfile::class);
});

function samConversation(User $user): AiConversation
{
    return AiConversation::query()
        ->where('user_id', $user->getKey())
        ->where('profile', 'sam')
        ->firstOrFail();
}

it('redacts the confirmation phrase before persisting the reply', function (): void {
    SamAgent::fake(['El borrador está listo. Confírmalo con CONFIRM ABC12345']);

    $user = User::factory()->create();

    $reply = app(SamRuntime::class)->send($user, 'Propón un cambio');

    $assistant = samConversation($user)->messages()
        ->where('role', 'assistant')
        ->firstOrFail();

    expect($reply->text)->toContain('CONFIRM ABC12345')
        ->and($assistant->content)->not->toContain('ABC12345')
        ->and($assistant->content)->toContain(SamRedactor::PLACEHOLDER);
});

it('does not store a message that is only a confirmation phrase', function (): void {
    $user = User::factory()->create();

    $reply = app(SamRuntime::class)->send($user, 'CONFIRM ABC12345');

    expect($reply->error)->toBe('confirmation_phrase')
        ->and(AiChatMessage::query()->count())->toBe(0);
});

it('guarantees the declaration on the first assistant turn only', function (): void {
    SamAgent::fake(['Primera respuesta.', 'Segunda respuesta.']);

    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);

    $runtime->send($user, 'Hola');
    $runtime->send($user, 'Otra cosa');

    $assistant = samConversation($user)->messages()
        ->where('role', 'assistant')
        ->orderBy('created_at')
        ->get();

    expect($assistant)->toHaveCount(2)
        ->and($assistant[0]->content)->toStartWith((new FakeSamProfile)->declaration())
        ->and($assistant[1]->content)->not->toContain('Soy una IA');
});

it('redacts and detects confirmation phrases', function (): void {
    expect(SamRedactor::redact('Confirma con CONFIRM ABC12345 ahora'))
        ->toBe('Confirma con CONFIRM •••••••• ahora')
        ->and(SamRedactor::isConfirmationPhrase('  confirm ab12cd  '))->toBeTrue()
        ->and(SamRedactor::isConfirmationPhrase('CONFIRM ABCDE'))->toBeFalse()
        ->and(SamRedactor::isConfirmationPhrase('CONFIRM'))->toBeFalse()
        ->and(SamRedactor::containsPhrase('texto CONFIRM ABC12345 texto'))->toBeTrue()
        ->and(SamRedactor::containsPhrase('texto CONFIRM ABCDE texto'))->toBeFalse()
        ->and(SamRedactor::containsPhrase('texto sin frase'))->toBeFalse();
});
