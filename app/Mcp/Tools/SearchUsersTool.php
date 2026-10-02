<?php

namespace App\Mcp\Tools;

use App\Actions\SearchUsers;
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
#[Name('search-users')]
#[Description('Search user accounts by name or email, optionally filtered by status, with pagination. Available to root and admin identities.')]
class SearchUsersTool extends ReadOnlyTool
{
    protected function authorize(User $user): bool
    {
        return $user->can('viewAny', User::class);
    }

    protected function perform(Request $request): Response|ResponseFactory
    {
        $maximum = (int) config('mcp_server.pagination.max_per_page');

        $data = $request->validate([
            'query' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', SearchUsers::STATUSES)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.$maximum],
        ], [
            'status.in' => 'The status must be one of: '.implode(', ', SearchUsers::STATUSES).'.',
            'per_page.max' => "The per_page value may not be greater than {$maximum}.",
        ]);

        $status = (string) ($data['status'] ?? 'all');
        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? config('mcp_server.pagination.per_page'));

        $users = app(SearchUsers::class)->handle((string) ($data['query'] ?? ''), $status, $perPage, $page);

        $this->auditContext = ['status' => $status, 'page' => $page, 'per_page' => $perPage];
        $this->auditResultCount = $users->count();

        return Response::structured([
            'data' => $users->getCollection()->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->all(),
                'status' => $user->isSuspended() ? 'suspended' : 'active',
                'email_verified' => $user->email_verified_at !== null,
                'created_at' => $user->created_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $this->authorize($user);
    }

    /**
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()->items($schema->object([
                'id' => $schema->integer()->required(),
                'name' => $schema->string()->required(),
                'email' => $schema->string()->required(),
                'roles' => $schema->array()->items($schema->string())->required(),
                'status' => $schema->string()->enum(['active', 'suspended'])->required(),
                'email_verified' => $schema->boolean()->required(),
                'created_at' => $schema->string()->nullable(),
            ]))->required(),
            'meta' => $schema->object([
                'page' => $schema->integer()->required(),
                'per_page' => $schema->integer()->required(),
                'total' => $schema->integer()->required(),
                'last_page' => $schema->integer()->required(),
            ])->required(),
        ];
    }
}
