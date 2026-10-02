<?php

namespace App\Mcp\Tools;

use App\Mcp\Capabilities;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Name('list-capabilities')]
#[Description('List the read-only capabilities available to the connected identity.')]
class ListCapabilitiesTool extends ReadOnlyTool
{
    protected function authorize(User $user): bool
    {
        return true;
    }

    protected function perform(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        $capabilities = $user instanceof User ? Capabilities::availableTo($user) : [];

        $this->auditResultCount = count($capabilities);

        return Response::structured(['capabilities' => $capabilities]);
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'capabilities' => $schema->array()
                ->items($schema->object([
                    'name' => $schema->string()->required(),
                    'description' => $schema->string()->required(),
                    'requirements' => $schema->string()->required(),
                ]))
                ->required(),
        ];
    }
}
