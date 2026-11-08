<?php

namespace Tests\Fixtures\Sam;

use App\Ai\Sam\SamCapability;
use App\Ai\Sam\SamCapabilityProvider;
use App\Ai\Sam\SamMode;
use App\Ai\Sam\SamRisk;
use Tests\Fixtures\Mcp\UpdateFooterTextTool;

/**
 * Contributor that declares a capability manifest for the tests.
 *
 * `$mode` switches the manifest between a consistent declaration and the
 * drifts `sam:doctor` must report.
 */
class CapabilitySamContributor extends FakeSamContributor implements SamCapabilityProvider
{
    public static string $mode = 'ok';

    /**
     * @return list<SamCapability>
     */
    public function capabilities(): array
    {
        return match (self::$mode) {
            'missing_handler' => [
                SamCapability::read('fake.read', 'fake', 'Read fake records'),
            ],
            'mcp_mismatch' => [
                SamCapability::propose(
                    key: 'fake.propose',
                    source: 'fake',
                    label: 'Propose a fake change',
                    risk: SamRisk::Irreversible,
                    permission: 'fake.other',
                    surfaces: ['sam', 'mcp'],
                    handlers: [FakeProposeTool::class, UpdateFooterTextTool::class],
                ),
            ],
            'override' => [
                SamCapability::propose(
                    key: 'fake.propose',
                    source: 'fake',
                    label: 'Propose a bulk change',
                    risk: SamRisk::Bulk,
                    minMode: SamMode::Assisted,
                    handlers: [FakeProposeTool::class],
                ),
            ],
            'partial' => [
                SamCapability::read(
                    key: 'fake.read',
                    source: 'fake',
                    label: 'Read fake records',
                    handlers: [FakeReadTool::class],
                ),
            ],
            default => [
                SamCapability::read(
                    key: 'fake.read',
                    source: 'fake',
                    label: 'Read fake records',
                    permission: 'fake.read',
                    handlers: [FakeReadTool::class],
                ),
                SamCapability::propose(
                    key: 'fake.propose',
                    source: 'fake',
                    label: 'Propose a fake change',
                    risk: SamRisk::Reversible,
                    undoable: true,
                    permission: 'fake.write',
                    handlers: [FakeProposeTool::class],
                ),
            ],
        };
    }
}
