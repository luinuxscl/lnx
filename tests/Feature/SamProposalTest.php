<?php

use App\Models\McpToolInvocation;
use App\Models\User;
use Carbon\CarbonInterface;
use Luinuxscl\LnxSam\SamProposal;
use Luinuxscl\LnxSam\SamProposalResult;
use Luinuxscl\LnxSam\SamRegistry;
use Luinuxscl\LnxSam\SamRisk;
use Luinuxscl\LnxSam\SamRuntime;
use Tests\Fixtures\Sam\FakeProposalPresenter;
use Tests\Fixtures\Sam\FakeSamContributor;
use Tests\Fixtures\Sam\FakeUndoablePresenter;

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

it('returns the pending drafts of every module for the actor', function (): void {
    $presenter = fakePresenter();
    $presenter->pending = [proposalFor('corr-1'), proposalFor('corr-2')];

    grantSamPermission();

    $proposals = app(SamRuntime::class)->pendingProposals(User::factory()->create(), 5);

    expect($proposals)->toHaveCount(2);
});

it('bounds the pending queue to the requested limit', function (): void {
    $presenter = fakePresenter();
    $presenter->pending = [proposalFor('corr-1'), proposalFor('corr-2'), proposalFor('corr-3')];

    grantSamPermission();

    $proposals = app(SamRuntime::class)->pendingProposals(User::factory()->create(), 1);

    expect($proposals)->toHaveCount(1);
});

it('returns no pending drafts to an unauthorized identity', function (): void {
    $presenter = fakePresenter();
    $presenter->pending = [proposalFor()];

    expect(app(SamRuntime::class)->pendingProposals(User::factory()->create(), 5))->toBe([]);
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

it('relaxes the phrase only when the module declares that it may', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = fakePresenter();
    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);

    $reversible = new SamProposal(
        source: 'fake',
        id: 'draft-1',
        correlationId: 'corr-1',
        title: 'Actualizar registro',
        summary: 'Cambia el nombre del registro.',
        changes: [['field' => 'name', 'label' => 'Nombre', 'old' => 'Ada', 'new' => 'Ada Lovelace']],
        status: 'pending',
        expiresAt: now()->addHour(),
        canConfirm: true,
        risk: SamRisk::Reversible,
        requiresPhrase: false,
    );

    $presenter->proposals['corr-1'] = [$reversible];
    $presenter->result = SamProposalResult::confirmed('Confirmed.');

    expect($runtime->confirm($user, $reversible, '')->succeeded())->toBeTrue()
        ->and($presenter->confirmCalls)->toBe(1);
});

it('keeps the phrase for an irreversible draft', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = fakePresenter();
    $user = User::factory()->create();
    $runtime = app(SamRuntime::class);

    $irreversible = new SamProposal(
        source: 'fake',
        id: 'draft-1',
        correlationId: 'corr-1',
        title: 'Archivar registro',
        summary: 'Archiva el registro.',
        changes: [['field' => 'archived', 'label' => 'Archivado', 'old' => 'No', 'new' => 'Sí']],
        status: 'pending',
        expiresAt: now()->addHour(),
        canConfirm: true,
        risk: SamRisk::Irreversible,
    );

    $presenter->proposals['corr-1'] = [$irreversible];
    $presenter->result = SamProposalResult::confirmed('Confirmed.');

    expect($runtime->confirm($user, $irreversible, '')->status)->toBe(SamProposalResult::FAILED)
        ->and($presenter->confirmCalls)->toBe(0);

    expect($runtime->confirm($user, $irreversible, 'CONFIRM ABC12345')->succeeded())->toBeTrue()
        ->and($presenter->confirmCalls)->toBe(1);
});

it('delegates the undo in the module and audits it', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = new FakeUndoablePresenter;
    app()->instance(FakeProposalPresenter::class, $presenter);

    $user = User::factory()->create();
    $proposal = proposalFor();

    $presenter->proposals['corr-1'] = [$proposal];

    $result = app(SamRuntime::class)->undo($user, $proposal);

    $event = McpToolInvocation::query()->where('tool', 'sam_undo')->latest('id')->first();

    expect($result->succeeded())->toBeTrue()
        ->and($presenter->undoCalls)->toBe(1)
        ->and($presenter->undoneId)->toBe('draft-1')
        ->and($event->status)->toBe('confirmed')
        ->and($event->correlation_id)->toBe('corr-1')
        ->and($event->context['action'])->toBe('undo')
        ->and($event->context['proposal_id'])->toBe('draft-1');
});

it('refuses the undo when the module cannot reverse the change', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = fakePresenter();
    $user = User::factory()->create();
    $proposal = proposalFor();

    $presenter->proposals['corr-1'] = [$proposal];

    $result = app(SamRuntime::class)->undo($user, $proposal);

    $event = McpToolInvocation::query()->where('tool', 'sam_undo')->latest('id')->first();

    expect($result->status)->toBe(SamProposalResult::FAILED)
        ->and($event->context['reason'])->toBe('not_undoable');
});

it('refuses the undo of a proposal that does not exist for the actor', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = new FakeUndoablePresenter;
    app()->instance(FakeProposalPresenter::class, $presenter);

    $result = app(SamRuntime::class)->undo(User::factory()->create(), proposalFor('missing'));

    expect($result->status)->toBe(SamProposalResult::FAILED)
        ->and($presenter->undoCalls)->toBe(0);
});

it('preserves a duplicate undo without presenting it as success', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = new FakeUndoablePresenter;
    app()->instance(FakeProposalPresenter::class, $presenter);

    $presenter->proposals['corr-1'] = [proposalFor()];
    $presenter->undoResult = SamProposalResult::duplicate('Already undone.');

    $result = app(SamRuntime::class)->undo(User::factory()->create(), proposalFor());

    expect($result->status)->toBe(SamProposalResult::DUPLICATE)
        ->and($result->succeeded())->toBeFalse();
});

it('requires the surface permission to undo', function (): void {
    configureSam();
    grantSamPermission();

    $presenter = new FakeUndoablePresenter;
    app()->instance(FakeProposalPresenter::class, $presenter);

    $presenter->proposals['corr-1'] = [proposalFor()];

    Gate::define('sam.use', fn (User $user): bool => false);

    expect(app(SamRuntime::class)->undo(User::factory()->create(), proposalFor())->status)
        ->toBe(SamProposalResult::FAILED)
        ->and($presenter->undoCalls)->toBe(0);
});
