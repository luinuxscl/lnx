<?php

use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\SamRuntime;
use App\Ai\Sam\SamTurn;
use App\Models\AiChatMessage;
use App\Models\McpToolInvocation;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Fixtures\Sam\DuplicateToolContributor;
use Tests\Fixtures\Sam\FakeProposeTool;
use Tests\Fixtures\Sam\FakeReadTool;
use Tests\Fixtures\Sam\FakeSamContributor;
use Tests\Fixtures\Sam\PlainTool;

beforeEach(function (): void {
    resetSamFixtures();
});

it('audits an authorized read with the turn correlation', function (): void {
    configureSam();
    grantSamPermission();

    $user = User::factory()->create();
    $this->actingAs($user);

    $correlationId = SamTurn::start('11111111-1111-1111-1111-111111111111');

    $output = app(FakeReadTool::class)->withSource('fake')->handle(new Request(['query' => 'ada']));

    $event = McpToolInvocation::query()->where('tool', 'fake_read')->latest('id')->first();

    expect($output)->toContain('Fake record')
        ->and($event)->not->toBeNull()
        ->and($event->status)->toBe('read')
        ->and($event->correlation_id)->toBe($correlationId)
        ->and($event->context['channel'])->toBe('sam')
        ->and($event->context['source'])->toBe('fake')
        ->and($event->context['action'])->toBe('read')
        ->and($event->result_count)->toBe(1);
});

it('returns an audited denial when the read is out of the actor scope', function (): void {
    configureSam();
    grantSamPermission();

    $user = User::factory()->create();
    $this->actingAs($user);

    FakeReadTool::$denies = true;

    $output = app(FakeReadTool::class)->withSource('fake')->handle(new Request(['query' => 'ada']));

    $event = McpToolInvocation::query()->where('tool', 'fake_read')->latest('id')->first();

    expect($output)->toContain('not available to this identity')
        ->and($event->status)->toBe('denied')
        ->and($event->context['reason'])->toBe('out_of_scope');
});

it('denies a tool to an identity without the surface permission', function (): void {
    configureSam();

    $user = User::factory()->create();
    $this->actingAs($user);

    $output = app(FakeReadTool::class)->withSource('fake')->handle(new Request(['query' => 'ada']));

    $event = McpToolInvocation::query()->where('tool', 'fake_read')->latest('id')->first();

    expect($output)->toContain('Permission denied')
        ->and($event->status)->toBe('denied')
        ->and($event->context['reason'])->toBe('forbidden');
});

it('returns a proposal draft without writing and audits it', function (): void {
    configureSam();
    grantSamPermission();

    $user = User::factory()->create();
    $this->actingAs($user);

    $output = app(FakeProposeTool::class)->withSource('fake')->handle(new Request(['title' => 'Nueva']));

    $event = McpToolInvocation::query()->where('tool', 'fake_propose')->latest('id')->first();

    expect($output)->toContain('CONFIRM ABC12345')
        ->and(FakeProposeTool::$calls)->toBe(1)
        ->and($event->status)->toBe('proposed')
        ->and($event->context['action'])->toBe('propose')
        ->and(AiChatMessage::query()->count())->toBe(0);
});

it('resolves only the kit read and proposal tools', function (): void {
    configureSam();
    grantSamPermission();

    app(SamRegistry::class)->register('fake', FakeSamContributor::class);

    $user = User::factory()->create();

    $tools = app(SamRuntime::class)->toolsFor($user);

    expect($tools)->toHaveCount(2)
        ->and($tools[0])->toBeInstanceOf(FakeReadTool::class)
        ->and($tools[1])->toBeInstanceOf(FakeProposeTool::class)
        ->and($tools[0]->source())->toBe('fake');
});

it('denies a tool while the agent is disabled', function (): void {
    configureSam();
    grantSamPermission();
    config()->set('lnx_sam.enabled', false);

    $user = User::factory()->create();
    $this->actingAs($user);

    $output = app(FakeReadTool::class)->withSource('fake')->handle(new Request(['query' => 'ada']));

    $event = McpToolInvocation::query()->where('tool', 'fake_read')->latest('id')->first();

    expect($output)->toContain('disabled')
        ->and($event->status)->toBe('denied')
        ->and($event->context['reason'])->toBe('disabled');
});

it('rejects duplicate tool names across contributors', function (): void {
    configureSam();
    grantSamPermission();

    app(SamRegistry::class)->register('fake', FakeSamContributor::class);
    app(SamRegistry::class)->register('other', DuplicateToolContributor::class);

    $tools = app(SamRuntime::class)->toolsFor(User::factory()->create());

    $event = McpToolInvocation::query()
        ->where('tool', FakeReadTool::class)
        ->latest('id')
        ->first();

    expect($tools)->toHaveCount(2)
        ->and($event)->not->toBeNull()
        ->and($event->status)->toBe('denied')
        ->and($event->context['reason'])->toBe('duplicate_name')
        ->and($event->context['source'])->toBe('other');
});

it('rejects and audits a tool that is not declared with the kit bases', function (): void {
    configureSam();
    grantSamPermission();

    FakeSamContributor::$withPlainTool = true;
    app(SamRegistry::class)->register('fake', FakeSamContributor::class);

    $user = User::factory()->create();

    $tools = app(SamRuntime::class)->toolsFor($user);

    $event = McpToolInvocation::query()
        ->where('tool', PlainTool::class)
        ->latest('id')
        ->first();

    expect($tools)->toHaveCount(2)
        ->and($event)->not->toBeNull()
        ->and($event->status)->toBe('denied')
        ->and($event->context['reason'])->toBe('not_declared');
});
