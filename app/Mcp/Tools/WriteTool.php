<?php

namespace App\Mcp\Tools;

use App\Actions\RecordMcpInvocation;
use App\Actions\RecordMcpWrite;
use App\Mcp\Attributes\WritesData;
use App\Models\McpWriteOperation;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use ReflectionClass;

/**
 * Base class for MCP tools that write data.
 *
 * Writing is a capability, not a side effect of being installed: it stays off
 * until the deployment is turned on, it requires an explicit {@see WritesData}
 * declaration, a declared permission and an idempotency key when applicable,
 * and every successful operation leaves an audited diff of what changed. The
 * tool never writes against the model directly — it delegates to the shared
 * domain action.
 */
#[IsDestructive]
abstract class WriteTool extends Tool
{
    /**
     * Non-sensitive structural context stored with the audit entry.
     *
     * @var array<string, mixed>
     */
    protected array $auditContext = [];

    /**
     * The fields the write changed, as field => ['old' => mixed, 'new' => mixed].
     *
     * @var array<string, array{old: mixed, new: mixed}>
     */
    protected array $auditDiff = [];

    /**
     * Identifier of the written record, stored with the audit entry.
     */
    protected ?string $auditResourceId = null;

    /**
     * Determine whether the given user may invoke the tool.
     */
    abstract protected function authorize(User $user): bool;

    /**
     * Execute the write by delegating to a shared domain action.
     */
    abstract protected function perform(Request $request): Response|ResponseFactory;

    /**
     * Authorize, execute with idempotency and audit the write invocation.
     */
    final public function handle(Request $request): Response|ResponseFactory
    {
        $declaration = static::declaration();

        if (! (bool) config('mcp_server.writes.enabled')) {
            return $this->deny(null, 'Write operations are disabled.');
        }

        $authenticated = $request->user();
        $user = $authenticated instanceof User ? $authenticated : null;

        if ($user === null) {
            return $this->deny(null, 'Authentication is required.', McpWriteOperation::STATUS_UNAUTHENTICATED);
        }

        if (! $this->allows($user)) {
            return $this->deny($user, 'You are not allowed to perform this operation.');
        }

        if ($this->rateLimited($user)) {
            return $this->deny($user, 'Too many write requests. Try again later.');
        }

        $idempotencyKey = null;
        $requestHash = null;

        if ($declaration->idempotent) {
            try {
                $validated = $request->validate([
                    'idempotency_key' => ['required', 'string', 'max:191'],
                ]);
            } catch (ValidationException $exception) {
                return $this->deny($user, $this->validationMessage($exception), McpWriteOperation::STATUS_INVALID);
            }

            $idempotencyKey = (string) $validated['idempotency_key'];
            $requestHash = $this->requestHash($request);

            $existing = app(RecordMcpWrite::class)->find($user, $this->name(), $idempotencyKey);

            if ($existing !== null) {
                $this->recordInvocation($user, McpWriteOperation::STATUS_DUPLICATE);

                return $this->replay($existing);
            }
        }

        try {
            $response = $this->perform($request);
        } catch (ValidationException $exception) {
            return $this->deny($user, $this->validationMessage($exception), McpWriteOperation::STATUS_INVALID);
        }

        $this->recordInvocation($user, McpWriteOperation::STATUS_SUCCESS);

        app(RecordMcpWrite::class)->handle(
            $user,
            $this->name(),
            $declaration->resource,
            $declaration->operation,
            McpWriteOperation::STATUS_SUCCESS,
            $this->correlationId(),
            $this->auditResourceId,
            $idempotencyKey,
            $requestHash,
            $this->diff(),
            $this->structuredPayload($response),
        );

        return $response;
    }

    /**
     * Whether the given user may see and invoke the tool.
     */
    public function allows(User $user): bool
    {
        if (! (bool) config('mcp_server.writes.enabled')) {
            return false;
        }

        $declaration = static::declaration();

        if ($declaration->permission !== null && ! $user->can($declaration->permission)) {
            return false;
        }

        return $this->authorize($user);
    }

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $this->allows($user);
    }

    /**
     * Read the mandatory {@see WritesData} declaration of the tool.
     *
     * @throws InvalidArgumentException when the tool writes without declaring it
     */
    public static function declaration(): WritesData
    {
        $attributes = (new ReflectionClass(static::class))->getAttributes(WritesData::class);

        if ($attributes === []) {
            throw new InvalidArgumentException(
                static::class.' writes data and must declare #[WritesData(resource: ..., operation: ...)].'
            );
        }

        return $attributes[0]->newInstance();
    }

    private function deny(?User $user, string $message, string $status = McpWriteOperation::STATUS_DENIED): Response
    {
        $this->recordInvocation($user, $status);

        return Response::error($message);
    }

    private function recordInvocation(?User $user, string $status): void
    {
        app(RecordMcpInvocation::class)->handle(
            $user,
            $this->name(),
            $status,
            $this->correlationId(),
            null,
            $this->auditContext,
        );
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>|null
     */
    private function diff(): ?array
    {
        if ($this->auditDiff === [] || ! (bool) config('mcp_server.audit.diff')) {
            return null;
        }

        return $this->auditDiff;
    }

    private function rateLimited(User $user): bool
    {
        $limit = (int) config('mcp_server.writes.rate_limit_per_minute');
        $key = 'mcp:write:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }

    private function replay(McpWriteOperation $operation): ResponseFactory
    {
        if (is_array($operation->response) && $operation->response !== []) {
            return Response::structured($operation->response);
        }

        return Response::structured([
            'idempotent_replay' => true,
            'operation_id' => $operation->id,
        ]);
    }

    private function requestHash(Request $request): string
    {
        $data = $request->all();
        unset($data['idempotency_key']);
        ksort($data);

        return hash('sha256', (string) json_encode($data));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function structuredPayload(Response|ResponseFactory $response): ?array
    {
        return $response instanceof ResponseFactory ? $response->getStructuredContent() : null;
    }

    private function validationMessage(ValidationException $exception): string
    {
        $messages = [];

        foreach ($exception->errors() as $field => $errors) {
            $messages[] = $field.': '.implode(' ', $errors);
        }

        return implode(' ', $messages);
    }

    private function correlationId(): string
    {
        $correlationId = request()->attributes->get('mcp.correlation_id');

        return is_string($correlationId) ? $correlationId : (string) Str::uuid();
    }
}
