<?php

namespace App\Actions;

use App\Ai\Sam\SamMode;
use App\Enums\AdministrativeAction;
use App\Models\SamModeCeiling;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

/**
 * Creates, updates or clears the mode ceiling of one role.
 *
 * The value is stored as chosen. The deployment ceiling is applied when the
 * mode is resolved, not here, so a ceiling that rises later does not need the
 * row to be rewritten.
 */
class UpdateSamModeCeiling
{
    public const INHERIT = 'inherit';

    public function __construct(private readonly RecordAdministrativeActivity $activity) {}

    /**
     * Persist the ceiling and record the change. Returns false when nothing changed.
     */
    public function handle(User $actor, string $role, string $value): bool
    {
        if (! Role::query()->where('name', $role)->exists()) {
            throw new InvalidArgumentException('That role does not exist.');
        }

        if ($value !== self::INHERIT && SamMode::tryFrom($value) === null) {
            throw new InvalidArgumentException('That mode is not valid.');
        }

        return DB::transaction(function () use ($actor, $role, $value): bool {
            $ceiling = SamModeCeiling::query()->where('role', $role)->first();

            if ($value === self::INHERIT) {
                if ($ceiling === null) {
                    return false;
                }

                $this->activity->handle($actor, AdministrativeAction::SamModeCeilingUpdated, $ceiling, [
                    'role' => $role,
                    'mode' => $ceiling->mode->value,
                ], [
                    'role' => $role,
                    'mode' => self::INHERIT,
                ]);
                $ceiling->delete();

                return true;
            }

            $mode = SamMode::from($value);

            if ($ceiling !== null && $ceiling->mode === $mode) {
                return false;
            }

            $previous = $ceiling?->mode->value;
            $ceiling ??= new SamModeCeiling(['role' => $role]);
            $ceiling->mode = $mode;
            $ceiling->save();

            $this->activity->handle($actor, AdministrativeAction::SamModeCeilingUpdated, $ceiling, [
                'role' => $role,
                'mode' => $previous,
            ], [
                'role' => $role,
                'mode' => $mode->value,
            ]);

            return true;
        });
    }
}
