<?php

namespace Tests\Fixtures\Sam;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Luinuxscl\LnxSam\Tools\SamReadResult;
use Luinuxscl\LnxSam\Tools\SamReadTool;

class FakeReadTool extends SamReadTool
{
    public const TOOL_NAME = 'fake_read';

    public static bool $denies = false;

    public static int $calls = 0;

    public function description(): string
    {
        return 'Read fake records.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Search term.')->required(),
        ];
    }

    protected function run(Request $request, User $user): SamReadResult
    {
        self::$calls++;

        if (self::$denies) {
            throw new AuthorizationException('Out of scope.');
        }

        return new SamReadResult(
            ['records' => [['id' => 1, 'name' => 'Fake record']]],
            'fake',
            '1',
            'Fake record',
            1,
        );
    }
}
