<?php

namespace App\Actions;

use App\Enums\AdministrativeAction;
use App\Models\AdministrativeActivity;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Database\Eloquent\Model;

class RecordAdministrativeActivity
{
    private const FIELDS = [
        'user' => ['name', 'email', 'role', 'suspended_at', 'avatar_path'],
        'invitation' => ['email', 'role', 'locale', 'expires_at', 'sent_at', 'send_failed_at', 'accepted_at', 'revoked_at'],
        'setting' => ['site_title', 'site_tagline', 'meta_description', 'admin_email', 'footer_text', 'analytics_id', 'maintenance_mode', 'registration_mode', 'maintenance_message', 'logo_path', 'logo_dark_path', 'favicon_path', 'apple_touch_icon_path'],
    ];

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function handle(?User $actor, AdministrativeAction $action, Model $resource, array $before = [], array $after = [], ?int $invitationId = null): ?AdministrativeActivity
    {
        $type = match (true) {
            $resource instanceof User => 'user',
            $resource instanceof UserInvitation => 'invitation',
            $resource instanceof Setting => 'setting',
            default => throw new \InvalidArgumentException('Unsupported administrative resource.'),
        };

        $changes = [];
        foreach (self::FIELDS[$type] as $field) {
            if (array_key_exists($field, $before) || array_key_exists($field, $after)) {
                $old = $before[$field] ?? null;
                $new = $after[$field] ?? null;
                if ($old !== $new) {
                    $changes[$field] = ['old' => $old, 'new' => $new];
                }
            }
        }

        if ($changes === [] && in_array($action, [AdministrativeAction::UserUpdated, AdministrativeAction::SettingsUpdated], true)) {
            return null;
        }

        return AdministrativeActivity::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'action' => $action,
            'resource_type' => $type,
            'resource_id' => $resource->getKey(),
            'resource_name' => $resource instanceof User ? $resource->name : null,
            'resource_email' => $resource instanceof User || $resource instanceof UserInvitation ? $resource->email : null,
            'invitation_id' => $invitationId,
            'changes' => $changes,
            'occurred_at' => now(),
        ]);
    }
}
