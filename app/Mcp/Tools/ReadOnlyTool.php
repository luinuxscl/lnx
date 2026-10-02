<?php

namespace App\Mcp\Tools;

use App\Actions\RecordMcpInvocation;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
abstract class ReadOnlyTool extends Tool
{
    /**
     * Non-sensitive structural context stored with the audit entry.
     *
     * @var array<string, mixed>
     */
    protected array $auditContext = [];

    protected ?int $auditResultCount = null;

    /**
     * Determine whether the given user may invoke the tool.
     */
    abstract protected function authorize(User $user): bool;

    /**
     * Execute the read operation.
     */
    abstract protected function perform(Request $request): Response|ResponseFactory;

    /**
     * Authorize, execute and audit the tool invocation.
     */
    final public function handle(Request $request): Response|ResponseFactory
    {
        $authenticated = $request->user();
        $user = $authenticated instanceof User ? $authenticated : null;

        if ($user === null) {
            return $this->record(null, 'unauthenticated', Response::error('Authentication is required.'));
        }

        if (! $this->authorize($user)) {
            return $this->record($user, 'denied', Response::error('You are not allowed to perform this operation.'));
        }

        try {
            $response = $this->perform($request);
        } catch (ValidationException $exception) {
            return $this->record($user, 'invalid', Response::error($this->validationMessage($exception)));
        }

        return $this->record($user, 'success', $response);
    }

    private function record(?User $user, string $status, Response|ResponseFactory $response): Response|ResponseFactory
    {
        app(RecordMcpInvocation::class)->handle(
            $user,
            $this->name(),
            $status,
            $this->correlationId(),
            $this->auditResultCount,
            $this->auditContext,
        );

        return $response;
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
