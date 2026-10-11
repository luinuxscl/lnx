<?php

namespace App\Support\RecordAccess;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Contract for a model whose rows are filtered by identity.
 *
 * A module relates a user with the records that user may see and exposes the
 * relation as a query scope. The filter is applied in the query
 * (authorization-first), never in memory: shared surfaces (MCP, admin) call
 * `accessibleTo($user)` and revalidate the per-operation permission, so no
 * surface ever returns a record without passing through this scope. A child
 * record inherits its parent's access by joining against the parent's scope.
 */
interface AccessControlled
{
    /**
     * Scope the query to the records the user is allowed to see.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder;
}
