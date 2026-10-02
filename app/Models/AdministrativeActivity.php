<?php

namespace App\Models;

use App\Enums\AdministrativeAction;
use Database\Factories\AdministrativeActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $actor_id
 * @property string|null $actor_name
 * @property string|null $actor_email
 * @property AdministrativeAction $action
 * @property string $resource_type
 * @property int|null $resource_id
 * @property string|null $resource_name
 * @property string|null $resource_email
 * @property int|null $invitation_id
 * @property array<string, mixed>|null $changes
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['actor_id', 'actor_name', 'actor_email', 'action', 'resource_type', 'resource_id', 'resource_name', 'resource_email', 'invitation_id', 'changes', 'occurred_at'])]
class AdministrativeActivity extends Model
{
    /** @use HasFactory<AdministrativeActivityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['action' => AdministrativeAction::class, 'changes' => 'array', 'occurred_at' => 'datetime'];
    }
}
