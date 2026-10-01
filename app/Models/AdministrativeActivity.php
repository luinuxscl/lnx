<?php

namespace App\Models;

use App\Enums\AdministrativeAction;
use Database\Factories\AdministrativeActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
