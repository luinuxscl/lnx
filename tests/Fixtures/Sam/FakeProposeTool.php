<?php

namespace Tests\Fixtures\Sam;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Luinuxscl\LnxSam\Tools\SamProposeTool;

class FakeProposeTool extends SamProposeTool
{
    public const TOOL_NAME = 'fake_propose';

    public static int $calls = 0;

    public function description(): string
    {
        return 'Propose a fake change.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Title of the fake record.')->required(),
        ];
    }

    protected function run(Request $request, User $user): string
    {
        self::$calls++;

        return '{"draft_id":"draft-1","confirmation_phrase":"CONFIRM ABC12345"}';
    }
}
