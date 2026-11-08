<?php

use App\Ai\Sam\SamCapability;
use App\Ai\Sam\SamCapabilityKind;
use App\Ai\Sam\SamMode;
use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\SamRisk;
use App\Mcp\McpToolRegistry;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Tests\Fixtures\Mcp\UpdateFooterTextTool;
use Tests\Fixtures\Sam\CapabilitySamContributor;
use Tests\Fixtures\Sam\DuplicateCapabilityContributor;
use Tests\Fixtures\Sam\FakeProposeTool;
use Tests\Fixtures\Sam\FakeReadTool;
use Tests\Fixtures\Sam\FakeSamContributor;
use Tests\Fixtures\Sam\ForeignCapabilityContributor;

beforeEach(function (): void {
    resetSamFixtures();

    config()->set('lnx_sam.max_mode', 'draft');
});

/**
 * Register the fake module and grant the permissions its manifest declares.
 */
function registerCapabilityModule(): void
{
    app(SamRegistry::class)->register('fake', CapabilitySamContributor::class);

    Gate::define('sam.use', fn (User $user): bool => true);
    Gate::define('fake.read', fn (User $user): bool => true);
    Gate::define('fake.write', fn (User $user): bool => true);
}

/**
 * Run one introspection command and return its exit code and output.
 *
 * @param  array<string, mixed>  $parameters
 * @return array{code: int, output: string}
 */
function runSamCommand(string $command, array $parameters = []): array
{
    $code = Artisan::call($command, $parameters);

    return ['code' => $code, 'output' => Artisan::output()];
}

it('lists the declared capabilities', function (): void {
    registerCapabilityModule();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:capabilities');

    expect($code)->toBe(0)
        ->and($output)->toContain('fake.read')
        ->and($output)->toContain('fake.propose')
        ->and($output)->toContain('Reversible')
        ->and($output)->toContain('click');
});

it('prints the catalogue as json', function (): void {
    registerCapabilityModule();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:capabilities', ['--json' => true]);

    $decoded = json_decode($output, true);

    expect($code)->toBe(0)
        ->and($decoded['capabilities'])->toHaveCount(2)
        ->and($decoded['capabilities'][0]['handlers'])->toBe([FakeReadTool::class])
        ->and($decoded['pending'])->toBe([]);
});

it('fails in strict mode when an installed module declares no manifest', function (): void {
    app(SamRegistry::class)->register('fake', FakeSamContributor::class);

    ['code' => $code, 'output' => $output] = runSamCommand('sam:capabilities', ['--strict' => true]);

    expect($code)->toBe(1)
        ->and($output)->toContain('PENDING fake');
});

it('describes one capability and its handlers', function (): void {
    registerCapabilityModule();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:describe', ['capability' => 'fake.propose']);

    expect($code)->toBe(0)
        ->and($output)->toContain('Propose a fake change')
        ->and($output)->toContain(FakeProposeTool::class)
        ->and($output)->toContain('sam propose')
        ->and($output)->toContain('Confirmation:  click');
});

it('fails describing an unknown capability', function (): void {
    registerCapabilityModule();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:describe', ['capability' => 'fake.unknown']);

    expect($code)->toBe(1)
        ->and($output)->toContain('is not declared');
});

it('reports a capability without a handler as drift', function (): void {
    CapabilitySamContributor::$mode = 'missing_handler';

    registerCapabilityModule();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:doctor');

    expect($code)->toBe(1)
        ->and($output)->toContain('declares no handler');
});

it('reports an MCP tool whose permission contradicts the capability', function (): void {
    CapabilitySamContributor::$mode = 'mcp_mismatch';

    registerCapabilityModule();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:doctor');

    expect($code)->toBe(1)
        ->and($output)->toContain('declares the permission [tests.write]');
});

it('reports a registered write tool without a capability', function (): void {
    registerCapabilityModule();

    app(McpToolRegistry::class)->register('fake', [UpdateFooterTextTool::class]);

    ['code' => $code, 'output' => $output] = runSamCommand('sam:doctor');

    expect($code)->toBe(1)
        ->and($output)->toContain('is registered without a capability');
});

it('reports a Sam tool exposed without a capability for the given actor', function (): void {
    CapabilitySamContributor::$mode = 'partial';

    registerCapabilityModule();

    $user = User::factory()->create();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:doctor', ['--actor' => $user->email]);

    expect($code)->toBe(1)
        ->and($output)->toContain('is exposed without a capability');
});

it('passes the doctor when the manifest matches the tools', function (): void {
    registerCapabilityModule();

    $user = User::factory()->create();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:doctor', ['--actor' => $user->email]);

    expect($code)->toBe(0)
        ->and($output)->toContain('No drift');
});

it('refuses to run without dry-run', function (): void {
    registerCapabilityModule();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:run', ['capability' => 'fake.read']);

    expect($code)->toBe(1)
        ->and($output)->toContain('pass --dry-run');
});

it('requires an actor to run the preflight', function (): void {
    registerCapabilityModule();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:run', [
        'capability' => 'fake.read',
        '--dry-run' => true,
    ]);

    expect($code)->toBe(1)
        ->and($output)->toContain('Pass --actor');
});

it('checks a read capability and returns the tool output', function (): void {
    configureSam();
    registerCapabilityModule();

    $user = User::factory()->create();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:run', [
        'capability' => 'fake.read',
        '--actor' => $user->email,
        '--input' => '{"query":"ada"}',
        '--dry-run' => true,
    ]);

    expect($code)->toBe(0)
        ->and($output)->toContain('is available to')
        ->and($output)->toContain('Fake record');
});

it('refuses to propose below the mode the capability needs', function (): void {
    configureSam();
    config()->set('lnx_sam.max_mode', 'read_only');
    registerCapabilityModule();

    $user = User::factory()->create();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:run', [
        'capability' => 'fake.propose',
        '--actor' => $user->email,
        '--dry-run' => true,
    ]);

    expect($code)->toBe(1)
        ->and($output)->toContain('the capability needs [draft]');
});

it('reports a proposal without preview instead of creating a draft', function (): void {
    configureSam();
    registerCapabilityModule();

    $user = User::factory()->create();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:run', [
        'capability' => 'fake.propose',
        '--actor' => $user->email,
        '--input' => '{"title":"Hola"}',
        '--dry-run' => true,
    ]);

    expect($code)->toBe(0)
        ->and($output)->toContain('declares no draft preview')
        ->and(FakeProposeTool::$calls)->toBe(0);
});

it('rejects an invalid json payload', function (): void {
    configureSam();
    registerCapabilityModule();

    $user = User::factory()->create();

    ['code' => $code, 'output' => $output] = runSamCommand('sam:run', [
        'capability' => 'fake.read',
        '--actor' => $user->email,
        '--input' => '{nope}',
        '--dry-run' => true,
    ]);

    expect($code)->toBe(1)
        ->and($output)->toContain('not valid JSON');
});

it('derives the tool risk and minimum mode from the capability', function (): void {
    CapabilitySamContributor::$mode = 'override';

    registerCapabilityModule();

    $tool = app(FakeProposeTool::class);

    expect($tool->risk())->toBe(SamRisk::Bulk)
        ->and($tool->minimumMode())->toBe(SamMode::Assisted);
});

it('keeps the tool constants when the module declares no capability', function (): void {
    app(SamRegistry::class)->register('fake', FakeSamContributor::class);

    $tool = app(FakeProposeTool::class);

    expect($tool->risk())->toBe(SamRisk::Reversible)
        ->and($tool->minimumMode())->toBe(SamMode::Draft);
});

it('exposes the declared manifest through the registry', function (): void {
    registerCapabilityModule();

    $registry = app(SamRegistry::class);

    expect($registry->capabilities())->toHaveCount(2)
        ->and($registry->capability('fake.read'))->toBeInstanceOf(SamCapability::class)
        ->and($registry->capability('fake.read')->kind)->toBe(SamCapabilityKind::Read)
        ->and($registry->capability('fake.propose')->minMode)->toBe(SamMode::Draft)
        ->and($registry->capabilitiesFor('fake'))->toHaveCount(2)
        ->and($registry->declaresCapabilities('fake'))->toBeTrue();
});

it('rejects a capability that declares another module as its source', function (): void {
    app(SamRegistry::class)->register('fake', ForeignCapabilityContributor::class);
})->throws(InvalidArgumentException::class, 'declares the source');

it('rejects a duplicated capability key', function (): void {
    app(SamRegistry::class)->register('fake', DuplicateCapabilityContributor::class);
})->throws(InvalidArgumentException::class, 'declared twice');

it('clears the manifest when the registry is flushed', function (): void {
    registerCapabilityModule();

    app(SamRegistry::class)->flush();

    expect(app(SamRegistry::class)->capabilities())->toBe([]);
});

it('requires a proposal to declare an undo before relaxing the phrase', function (): void {
    SamCapability::propose(
        key: 'fake.propose',
        source: 'fake',
        label: 'Propose',
        risk: SamRisk::Reversible,
    );
})->throws(InvalidArgumentException::class, 'declares no undo');

it('rejects an unknown handler class', function (): void {
    SamCapability::read(
        key: 'fake.read',
        source: 'fake',
        label: 'Read',
        handlers: ['Tests\\Fixtures\\Sam\\MissingTool'],
    );
})->throws(InvalidArgumentException::class, 'unknown handler');
