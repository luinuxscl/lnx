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
 * Base of the tools that let Sam propose a draft.
 *
 * The module delegates in its own draft action, which validates, authorizes,
 * previews and audits the draft. The tool returns the envelope to the model
 * and never writes: confirmation is a person's action, never a tool.
 */
abstract class SamProposeTool extends SamTool
{
    public const ACTION = 'propose';

    abstract protected function run(Request $request, User $user): string;

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
            $envelope = $this->run($request, $user);
        } catch (ValidationException $exception) {
            $this->audit($user, 'denied', null, ['reason' => 'validation']);

            return 'Invalid arguments: '.$this->validationMessage($exception);
        } catch (AuthorizationException|ModelNotFoundException) {
            $this->audit($user, 'denied', null, ['reason' => 'out_of_scope']);

            return 'The requested record is not available to this identity.';
        } catch (Throwable $exception) {
            report($exception);

            $this->audit($user, 'failed', null, ['reason' => 'error']);

            return 'The proposal could not be created.';
        }

        $this->audit($user, 'proposed', null, ['draft' => true]);

        return $envelope;
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
