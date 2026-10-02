<?php

namespace App\Mcp\Tools;

use App\Actions\ListAdministrativeActivity;
use App\Models\AdministrativeActivity;
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
#[Name('get-administrative-activity')]
#[Description('Read the administrative activity history with filters by actor, action and date range, plus pagination. Available to root identities only.')]
class GetAdministrativeActivityTool extends ReadOnlyTool
{
    protected function authorize(User $user): bool
    {
        return $user->can('viewAny', AdministrativeActivity::class);
    }

    protected function perform(Request $request): Response|ResponseFactory
    {
        $maximum = (int) config('mcp_server.pagination.max_per_page');

        $data = $request->validate(array_merge(ListAdministrativeActivity::rules(), [
            'actor' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.$maximum],
        ]), [
            'from.date_format' => 'The from date must use the Y-m-d format.',
            'to.date_format' => 'The to date must use the Y-m-d format.',
            'to.after_or_equal' => 'The to date must be on or after the from date.',
            'per_page.max' => "The per_page value may not be greater than {$maximum}.",
        ], ListAdministrativeActivity::attributeNames());

        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? config('mcp_server.pagination.per_page'));

        $activities = app(ListAdministrativeActivity::class)->handle([
            'actor' => (string) ($data['actor'] ?? ''),
            'action' => (string) ($data['action'] ?? ''),
            'from' => (string) ($data['from'] ?? ''),
            'to' => (string) ($data['to'] ?? ''),
        ], $perPage, $page);

        $this->auditContext = [
            'action' => (string) ($data['action'] ?? ''),
            'from' => (string) ($data['from'] ?? ''),
            'to' => (string) ($data['to'] ?? ''),
            'page' => $page,
            'per_page' => $perPage,
        ];
        $this->auditResultCount = $activities->count();

        return Response::structured([
            'data' => $activities->getCollection()->map(fn (AdministrativeActivity $activity): array => [
                'id' => $activity->id,
                'action' => $activity->action->value,
                'actor' => [
                    'id' => $activity->actor_id,
                    'name' => $activity->actor_name,
                    'email' => $activity->actor_email,
                ],
                'resource' => [
                    'type' => $activity->resource_type,
                    'id' => $activity->resource_id,
                    'name' => $activity->resource_name,
                    'email' => $activity->resource_email,
                ],
                'changes' => $activity->changes,
                'occurred_at' => $activity->occurred_at->toIso8601String(),
            ])->all(),
            'meta' => [
                'page' => $activities->currentPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
                'last_page' => $activities->lastPage(),
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
                'action' => $schema->string()->required(),
                'actor' => $schema->object([
                    'id' => $schema->integer()->nullable(),
                    'name' => $schema->string()->nullable(),
                    'email' => $schema->string()->nullable(),
                ])->required(),
                'resource' => $schema->object([
                    'type' => $schema->string()->required(),
                    'id' => $schema->integer()->nullable(),
                    'name' => $schema->string()->nullable(),
                    'email' => $schema->string()->nullable(),
                ])->required(),
                'changes' => $schema->object([])->nullable(),
                'occurred_at' => $schema->string()->required(),
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
