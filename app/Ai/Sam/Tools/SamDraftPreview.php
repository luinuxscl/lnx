<?php

namespace App\Ai\Sam\Tools;

use App\Models\User;

/**
 * Handler that can compute a draft without persisting it.
 *
 * `sam:run --dry-run` uses it to show what a confirmation would execute. A
 * handler that cannot preview is still valid; the command then reports the
 * authorization only, and never creates a draft to inspect it.
 */
interface SamDraftPreview
{
    /**
     * Whether the actor may propose this draft: permission and record scope.
     */
    public function allows(User $user): bool;

    /**
     * Preview and payload a confirmation would execute.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function proposeDraft(array $input, User $user): array;
}
