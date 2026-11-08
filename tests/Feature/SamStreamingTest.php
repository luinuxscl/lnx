<?php

use App\Ai\Knowledge\HelpSearchService;
use App\Models\AiChatMessage;
use App\Models\User;
use Luinuxscl\LnxSam\SamAgent;
use Luinuxscl\LnxSam\SamContext;
use Luinuxscl\LnxSam\SamInstructions;
use Luinuxscl\LnxSam\SamRegistry;
use Luinuxscl\LnxSam\SamRuntime;
use Tests\Fixtures\Sam\FakeSamContributor;

beforeEach(function (): void {
    resetSamFixtures();
});

function registerStreamingFake(): void
{
    app(SamRegistry::class)->register('fake', FakeSamContributor::class);
}

it('streams the answer through the callback and persists it', function (): void {
    configureSam();
    grantSamPermission();
    registerStreamingFake();

    SamAgent::fake(['Hola, soy Fake Sam.']);

    $user = User::factory()->create();
    $events = [];

    $reply = app(SamRuntime::class)->send(
        $user,
        'Hola',
        onEvent: function (array $event) use (&$events): void {
            $events[] = $event;
        },
    );

    $streamed = implode('', array_map(
        fn (array $event): string => $event['delta'] ?? '',
        $events,
    ));

    expect($reply->succeeded())->toBeTrue()
        ->and($streamed)->toBe('Hola, soy Fake Sam.')
        ->and($events[0]['type'])->toBe('text')
        ->and(AiChatMessage::query()->where('role', 'assistant')->value('content'))
        ->toBe('Hola, soy Fake Sam.');
});

it('falls back to a regular prompt when the model does not stream', function (): void {
    configureSam();
    config()->set('lnx_ai.allowed_models.test/sam-model.streaming', false);
    grantSamPermission();
    registerStreamingFake();

    SamAgent::fake(['Respuesta sin streaming.']);

    $user = User::factory()->create();
    $events = [];

    $reply = app(SamRuntime::class)->send(
        $user,
        'Hola',
        onEvent: function (array $event) use (&$events): void {
            $events[] = $event;
        },
    );

    expect($reply->succeeded())->toBeTrue()
        ->and($reply->text)->toBe('Respuesta sin streaming.')
        ->and($events)->toBe([]);
});

it('cites the help sources the answer mentions', function (): void {
    configureSam();
    grantSamPermission();
    registerStreamingFake();

    $registry = app(SamRegistry::class);
    $help = app(HelpSearchService::class);

    app()->instance(SamInstructions::class, new class($registry, $help) extends SamInstructions
    {
        public function compose(
            User $actor,
            string $message,
            ?SamContext $context = null,
            bool $firstTurn = false,
        ): string {
            return 'instrucciones de prueba';
        }

        public function lastSources(): array
        {
            return [[
                'slug' => 'invitar-personas',
                'title' => 'Cómo invitar personas',
                'url' => 'https://example.test/help/invitar-personas',
                'content' => 'Abre Usuarios y pulsa Invitar.',
            ]];
        }
    });

    SamAgent::fake(['Puedes hacerlo desde «Cómo invitar personas».']);

    $user = User::factory()->create();

    $reply = app(SamRuntime::class)->send($user, '¿Cómo invito a alguien?');

    expect($reply->citations)->toBe([[
        'type' => 'help',
        'slug' => 'invitar-personas',
        'title' => 'Cómo invitar personas',
        'url' => 'https://example.test/help/invitar-personas',
    ]])->and(AiChatMessage::query()->where('role', 'assistant')->value('citations'))
        ->toBe($reply->citations);
});

it('does not cite help sources the answer does not mention', function (): void {
    configureSam();
    grantSamPermission();
    registerStreamingFake();

    $registry = app(SamRegistry::class);
    $help = app(HelpSearchService::class);

    app()->instance(SamInstructions::class, new class($registry, $help) extends SamInstructions
    {
        public function compose(
            User $actor,
            string $message,
            ?SamContext $context = null,
            bool $firstTurn = false,
        ): string {
            return 'instrucciones de prueba';
        }

        public function lastSources(): array
        {
            return [[
                'slug' => 'invitar-personas',
                'title' => 'Cómo invitar personas',
                'url' => 'https://example.test/help/invitar-personas',
                'content' => 'Abre Usuarios y pulsa Invitar.',
            ]];
        }
    });

    SamAgent::fake(['No tengo esa información.']);

    $user = User::factory()->create();

    $reply = app(SamRuntime::class)->send($user, '¿Cómo invito a alguien?');

    expect($reply->citations)->toBe([]);
});
