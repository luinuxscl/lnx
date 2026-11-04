<?php

namespace App\Ai\Sam\Tools;

use App\Ai\Sam\SamAccess;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Tools\Request;
use Throwable;

/**
 * Base of the read-only tools Sam may call.
 *
 * The kit owns the authorization and the audit: the module only implements
 * `run()` against the identity's own permissions and record scope. A read out
 * of scope returns a clear denial and is audited as such.
 */
abstract class SamReadTool extends SamTool
{
    public const ACTION = 'read';

    abstract protected function run(Request $request, User $user): SamReadResult;

    final public function handle(Request $request): string
    {
        $user = $this->actor();

        if (! $user instanceof User) {
            $this->audit(null, 'denied', null, ['reason' => 'unauthenticated']);

            return 'Authentication is required.';
        }

        $access = app(SamAccess::class);

        if (! $access->enabled()) {
            $this->audit($user, 'denied', null, ['reason' => 'disabled']);

            return 'The agent is disabled.';
        }

        if (! $access->canUse($user)) {
            $this->audit($user, 'denied', null, ['reason' => 'forbidden']);

            return 'Permission denied: this identity cannot use the agent.';
        }

        try {
            $result = $this->run($request, $user);
        } catch (ValidationException $exception) {
            $this->audit($user, 'denied', null, ['reason' => 'validation']);

            return 'Invalid arguments: '.$this->validationMessage($exception);
        } catch (AuthorizationException|ModelNotFoundException) {
            $this->audit($user, 'denied', null, ['reason' => 'out_of_scope']);

            return 'The requested record is not available to this identity.';
        } catch (Throwable $exception) {
            report($exception);

            $this->audit($user, 'failed', null, ['reason' => 'error']);

            return 'The read could not be completed.';
        }

        $this->audit($user, 'read', $result->count, [
            'target_type' => $result->resource,
            'target_id' => $result->targetId,
            'target_label' => $result->targetLabel,
        ]);

        return $result->toJson();
    }

    private function validationMessage(ValidationException $exception): string
    {
        $messages = [];

        foreach ($exception->errors() as $field => $errors) {
            $messages[] = $field.': '.implode(' ', $errors);
        }

        return implode(' ', $messages);
    }
}
