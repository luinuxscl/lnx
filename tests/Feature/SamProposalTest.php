<?php

use App\Ai\Sam\SamProposal;
use App\Ai\Sam\SamProposalResult;
use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\SamRuntime;
use App\Models\McpToolInvocation;
use App\Models\User;
use Carbon\CarbonInterface;
use Tests\Fixtures\Sam\FakeProposalPresenter;
use Tests\Fixtures\Sam\FakeSamContributor;

beforeEach(function (): void {
    resetSamFixtures();

    FakeSamContributor::$withPresenter = true;
    app(SamRegistry::class)->register('fake', FakeSamContributor::class);
});

function fakePresenter(): FakeProposalPresenter
{
    $presenter = new FakeProposalPresenter;

    app()->instance(FakeProposalPresenter::class, $presenter);

    return $presenter;
}

function proposalFor(string $correlationId = 'corr-1', bool $canConfirm = true, ?CarbonInterface $expiresAt = null): SamProposal
{
    return new SamProposal(
        source: 'fake',
        id: 'draft-1',
        correlationId: $correlationId,
        title: 'Actualizar registro',
        summary: 'Cambia el nombre del registro.',
        changes: [['field' => 'name', 'label' => 'Nombre', 'old' => 'Ada', 'new' => 'Ada Lovelace']],
        status: 'pending',
        expiresAt: $expiresAt ?? now()->addHour(),
        recordUrl: 'https://example.test/fake/1',
        canConfirm: $canConfirm,
    );
}

it('returns the drafts of a correlation for the actor', function (): void {
    $presenter = fakePresenter();
    $presenter->proposals['corr-1'] = [proposalFor()];

    grantSamPermission();

    $proposals = app(SamRuntime::class)->proposals(User::factory()->create(), 'corr-1');

    expect($proposals)->toHaveCount(1)
        ->and($proposals[0]->id)->toBe('draft-1');
});

it('returns no drafts to an unauthorized identity', function (): void {
    $presenter = fakePresenter();
    $presenter->proposals['corr-1'] = [proposalFor()];

    expect(app(SamRuntime::class)->proposals(User::factory()->create(), 'corr-1'))->toBe([]);
});

it('delegates the confirmation in the module and audits it', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = fakePresenter();
    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);
    $proposal = proposalFor();

    $presenter->proposals['corr-1'] = [$proposal];
    $presenter->result = SamProposalResult::confirmed('Confirmed.');

    $result = $runtime->confirm($user, $proposal, 'CONFIRM ABC12345');

    $event = McpToolInvocation::query()->where('tool', 'sam_confirm')->latest('id')->first();

    expect($result->succeeded())->toBeTrue()
        ->and($presenter->confirmCalls)->toBe(1)
        ->and($event->status)->toBe('confirmed')
        ->and($event->correlation_id)->toBe('corr-1')
        ->and($event->context['action'])->toBe('confirm')
        ->and($event->context['proposal_id'])->toBe('draft-1');
});

it('preserves a duplicate result without presenting it as success', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = fakePresenter();
    $user = User::factory()->create();
    $proposal = proposalFor();

    $presenter->proposals['corr-1'] = [$proposal];
    $presenter->result = SamProposalResult::duplicate('Already confirmed.');

    $result = app(SamRuntime::class)->confirm($user, $proposal, 'CONFIRM ABC12345');

    expect($result->status)->toBe(SamProposalResult::DUPLICATE)
        ->and($result->succeeded())->toBeFalse();
});

it('rejects an expired proposal before calling the module', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = fakePresenter();
    $user = User::factory()->create();
    $proposal = proposalFor(expiresAt: now()->subMinute());

    $presenter->proposals['corr-1'] = [$proposal];

    $result = app(SamRuntime::class)->confirm($user, $proposal, 'CONFIRM ABC12345');

    expect($result->status)->toBe(SamProposalResult::EXPIRED)
        ->and($presenter->confirmCalls)->toBe(0);
});

it('rejects a proposal that cannot be confirmed', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = fakePresenter();
    $user = User::factory()->create();
    $proposal = proposalFor(canConfirm: false);

    $presenter->proposals['corr-1'] = [$proposal];

    $result = app(SamRuntime::class)->confirm($user, $proposal, 'CONFIRM ABC12345');

    expect($result->status)->toBe(SamProposalResult::FAILED)
        ->and($presenter->confirmCalls)->toBe(0);
});

it('rejects a proposal that does not exist for the actor', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = fakePresenter();

    $result = app(SamRuntime::class)->confirm(
        User::factory()->create(),
        proposalFor('missing'),
        'CONFIRM ABC12345',
    );

    expect($result->status)->toBe(SamProposalResult::FAILED)
        ->and($presenter->confirmCalls)->toBe(0);
});

it('requires a phrase and the surface permission', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = fakePresenter();
    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);
    $proposal = proposalFor();

    $presenter->proposals['corr-1'] = [$proposal];
    $presenter->result = SamProposalResult::confirmed('Confirmed.');

    expect($runtime->confirm($user, $proposal, ' ')->status)->toBe(SamProposalResult::FAILED)
        ->and($presenter->confirmCalls)->toBe(0);

    expect($runtime->confirm($user, $proposal, 'CONFIRM ABC12345')->succeeded())->toBeTrue()
        ->and($presenter->confirmCalls)->toBe(1);

    Gate::define('sam.use', fn (User $user): bool => false);

    expect($runtime->confirm($user, $proposal, 'CONFIRM ABC12345')->status)->toBe(SamProposalResult::FAILED)
        ->and($presenter->confirmCalls)->toBe(1);
});
