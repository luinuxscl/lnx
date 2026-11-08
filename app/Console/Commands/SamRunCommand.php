<?php

namespace App\Console\Commands;

use App\Ai\Sam\SamAccess;
use App\Ai\Sam\SamCapability;
use App\Ai\Sam\SamCapabilityInspector;
use App\Ai\Sam\SamCapabilityKind;
use App\Ai\Sam\SamRegistry;
use App\Ai\Sam\Tools\SamDraftPreview;
use App\Ai\Sam\Tools\SamReadTool;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use JsonException;
use Laravel\Ai\Tools\Request;

/**
 * Preflight of one capability for one identity.
 *
 * It never proposes and never writes: it checks the switch, the permission,
 * the effective mode and the handler resolution, and when the handler exposes
 * a draft preview it prints what a confirmation would execute. A proposal is
 * not persisted.
 */
class SamRunCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sam:run
        {capability : Key of the capability to check}
        {--actor= : Email or id of the identity the check runs as}
        {--input= : JSON payload the tool would receive}
        {--dry-run : Required: the command never writes}';

    /**
     * @var string
     */
    protected $description = 'Check a capability against one identity without calling the provider or writing';

    public function handle(
        SamRegistry $registry,
        SamAccess $access,
        SamCapabilityInspector $inspector,
    ): int {
        if (! $this->option('dry-run')) {
            $this->error('sam:run only inspects; pass --dry-run.');

            return self::FAILURE;
        }

        $key = trim((string) $this->argument('capability'));
        $capability = $registry->capability($key);

        if (! $capability instanceof SamCapability) {
            $this->error("The capability [{$key}] is not declared.");

            return self::FAILURE;
        }

        $problems = $inspector->problems($capability);

        if ($problems !== []) {
            foreach ($problems as $problem) {
                $this->error("DRIFT {$problem}");
            }

            return self::FAILURE;
        }

        $actor = $this->actor();

        if (! $actor instanceof User) {
            return self::FAILURE;
        }

        $input = $this->payload();

        if ($input === null) {
            return self::FAILURE;
        }

        if (! $this->authorize($access, $capability, $actor)) {
            return self::FAILURE;
        }

        return $capability->kind === SamCapabilityKind::Read
            ? $this->runRead($capability, $actor, $input)
            : $this->runPropose($capability, $actor, $input);
    }

    private function actor(): ?User
    {
        $value = $this->option('actor');

        if (! is_string($value) || trim($value) === '') {
            $this->error('Pass --actor= with the email or the id of the identity.');

            return null;
        }

        $value = trim($value);

        $actor = User::query()
            ->where('email', $value)
            ->when(ctype_digit($value), fn ($query) => $query->orWhere('id', (int) $value))
            ->first();

        if (! $actor instanceof User) {
            $this->error("The identity [{$value}] does not exist.");

            return null;
        }

        return $actor;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function payload(): ?array
    {
        $value = $this->option('input');

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error('The payload is not valid JSON: '.$exception->getMessage());

            return null;
        }

        if (! is_array($decoded)) {
            $this->error('The payload must be a JSON object.');

            return null;
        }

        return $decoded;
    }

    private function authorize(SamAccess $access, SamCapability $capability, User $actor): bool
    {
        if (! $access->enabled()) {
            $this->error('The agent is disabled in this deployment (SAM_ENABLED=false).');

            return false;
        }

        if (! $access->ready()) {
            $this->error('The agent runtime is not ready: the model is not configured or does not support tools.');

            return false;
        }

        if (! $access->canUse($actor)) {
            $this->error("The identity [{$actor->email}] cannot use the agent ({$access->permission()}).");

            return false;
        }

        if ($capability->permission !== null && ! $actor->can($capability->permission)) {
            $this->error("The identity [{$actor->email}] lacks the permission [{$capability->permission}].");

            return false;
        }

        $mode = $access->modeFor($actor);

        if (! $mode->allows($capability->minMode)) {
            $this->error(sprintf(
                'The effective mode of [%s] is [%s] and the capability needs [%s].',
                $actor->email,
                $mode->value,
                $capability->minMode->value,
            ));

            return false;
        }

        $this->info("OK    {$capability->key} is available to {$actor->email} (mode {$mode->value}).");

        return true;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function runRead(SamCapability $capability, User $actor, array $input): int
    {
        $tool = $this->readTool($capability);

        if (! $tool instanceof SamReadTool) {
            $this->error('The capability declares no Sam read tool to invoke.');

            return self::FAILURE;
        }

        Auth::setUser($actor);

        try {
            $result = $tool->withSource($capability->source)->handle(new Request($input));
        } finally {
            Auth::forgetUser();
        }

        $this->line($result);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function runPropose(SamCapability $capability, User $actor, array $input): int
    {
        $handler = $this->previewHandler($capability);

        if ($handler === null) {
            $this->warn('The capability declares no draft preview; authorization and mode were checked only.');

            return self::SUCCESS;
        }

        if (! $handler->allows($actor)) {
            $class = $handler::class;
            $this->error("The handler [{$class}] does not allow {$actor->email} to propose it.");

            return self::FAILURE;
        }

        try {
            $preview = $handler->proposeDraft($input, $actor);
        } catch (\Throwable $exception) {
            $this->error('The preview failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->line((string) json_encode(
            $preview,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        $this->info('No draft was created: the preview only.');

        return self::SUCCESS;
    }

    private function readTool(SamCapability $capability): ?SamReadTool
    {
        foreach ($capability->handlers as $handler) {
            if (is_subclass_of($handler, SamReadTool::class)) {
                return app($handler);
            }
        }

        return null;
    }

    /**
     * Handler that can compute the draft without persisting it.
     */
    private function previewHandler(SamCapability $capability): ?SamDraftPreview
    {
        foreach ($capability->handlers as $handler) {
            if (is_subclass_of($handler, SamDraftPreview::class)) {
                $instance = app($handler);

                return $instance instanceof SamDraftPreview ? $instance : null;
            }
        }

        return null;
    }
}
