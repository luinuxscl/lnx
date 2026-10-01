<?php

namespace App\Actions;

use App\Enums\AdministrativeAction;
use App\Enums\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class IssueUserInvitation
{
    public function create(User $actor, string $email, ?string $role, string $locale): bool
    {
        $email = Str::lower(trim($email));
        abort_unless($actor->can('create', UserInvitation::class), 403);

        if ($role !== null && ! in_array($role, Role::assignableValues(), true)) {
            throw ValidationException::withMessages(['role' => __('Invalid role.')]);
        }

        if (! in_array($locale, ['es', 'en'], true)) {
            throw ValidationException::withMessages(['locale' => __('Invalid invitation language.')]);
        }

        $this->limit($actor, $email);

        $token = Str::random(64);
        try {
            $invitation = DB::transaction(function () use ($actor, $email, $role, $locale, $token): UserInvitation {
                if (User::query()->whereRaw('lower(email) = ?', [$email])->exists()) {
                    throw ValidationException::withMessages(['email' => __('This email already belongs to a user.')]);
                }

                UserInvitation::query()->where('pending_email', $email)->where('expires_at', '<=', now())->update(['pending_email' => null]);

                if (UserInvitation::query()->where('pending_email', $email)->exists()) {
                    throw ValidationException::withMessages(['email' => __('A pending invitation exists. Resend it from the invitation list.')]);
                }

                $invitation = UserInvitation::create([
                    'email' => $email,
                    'pending_email' => $email,
                    'role' => $role,
                    'locale' => $locale,
                    'invited_by' => $actor->id,
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => now()->addDays(7),
                ]);
                app(RecordAdministrativeActivity::class)->handle($actor, AdministrativeAction::InvitationCreated, $invitation, [], [
                    'email' => $email, 'role' => $role, 'locale' => $locale, 'expires_at' => $invitation->expires_at->toIso8601String(),
                ]);

                return $invitation;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (UserInvitation::query()->where('pending_email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => __('A pending invitation exists. Resend it from the invitation list.')]);
            }

            throw $exception;
        }

        return $this->send($invitation, $token, $actor);
    }

    public function resend(User $actor, UserInvitation $invitation): bool
    {
        abort_unless($actor->can('update', $invitation), 403);
        $this->limit($actor, $invitation->email);

        if (User::query()->whereRaw('lower(email) = ?', [$invitation->email])->exists()) {
            throw ValidationException::withMessages(['email' => __('This email already belongs to a user.')]);
        }

        if (UserInvitation::query()->where('pending_email', $invitation->email)
            ->whereKeyNot($invitation->id)->exists()) {
            throw ValidationException::withMessages(['email' => __('A pending invitation exists. Resend it from the invitation list.')]);
        }

        $token = Str::random(64);
        try {
            $updated = DB::transaction(function () use ($actor, $invitation, $token): int {
                $current = UserInvitation::query()->whereKey($invitation->id)->firstOrFail();
                $updated = UserInvitation::query()
                    ->whereKey($invitation->id)
                    ->whereNull('accepted_at')
                    ->whereNull('revoked_at')
                    ->where('token_hash', $invitation->token_hash)
                    ->update([
                        'pending_email' => $invitation->email,
                        'token_hash' => hash('sha256', $token),
                        'expires_at' => now()->addDays(7),
                        'sent_at' => null,
                        'send_failed_at' => null,
                    ]);
                if ($updated === 1) {
                    $fresh = $current->fresh();
                    app(RecordAdministrativeActivity::class)->handle($actor, AdministrativeAction::InvitationRenewed, $fresh, [
                        'expires_at' => $current->expires_at->toIso8601String(),
                        'sent_at' => $current->sent_at?->toIso8601String(),
                        'send_failed_at' => $current->send_failed_at?->toIso8601String(),
                    ], [
                        'expires_at' => $fresh->expires_at->toIso8601String(),
                        'sent_at' => null,
                        'send_failed_at' => null,
                    ]);
                }

                return $updated;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => __('A pending invitation exists. Resend it from the invitation list.')]);
        }

        if ($updated !== 1) {
            throw ValidationException::withMessages(['invitation' => __('This invitation is no longer available.')]);
        }

        return $this->send($invitation->refresh(), $token, $actor);
    }

    private function limit(User $actor, string $email): void
    {
        $emailKey = hash('sha256', $email);
        $keys = [
            ['invite:minute:'.$emailKey, 1, 60],
            ['invite:hour:'.$emailKey, 5, 3600],
            ['invite:actor:'.$actor->id, 20, 3600],
        ];

        foreach ($keys as [$key, $maximum, $seconds]) {
            if (RateLimiter::tooManyAttempts($key, $maximum)) {
                throw ValidationException::withMessages(['email' => __('Please wait before sending another invitation.')]);
            }
        }

        foreach ($keys as [$key, $maximum, $seconds]) {
            RateLimiter::hit($key, $seconds);
        }
    }

    private function send(UserInvitation $invitation, string $token, ?User $actor = null): bool
    {
        try {
            Notification::route('mail', $invitation->email)->notify(new UserInvitationNotification(
                route('invitations.accept', ['invitation' => $invitation->id, 'token' => $token]),
                $invitation->locale,
            )->locale($invitation->locale));
        } catch (Throwable $exception) {
            report($exception);
            $this->recordSendResult($invitation, $token, $actor, false);

            return false;
        }

        $updated = $this->recordSendResult($invitation, $token, $actor, true);

        return $updated === 1;
    }

    private function recordSendResult(UserInvitation $invitation, string $token, ?User $actor, bool $sent): int
    {
        return DB::transaction(function () use ($invitation, $token, $actor, $sent): int {
            $current = UserInvitation::query()->whereKey($invitation->id)->firstOrFail();
            $updated = UserInvitation::query()->whereKey($invitation->id)
                ->whereNull('accepted_at')->whereNull('revoked_at')
                ->where('token_hash', hash('sha256', $token))
                ->update($sent ? ['sent_at' => now(), 'send_failed_at' => null] : ['send_failed_at' => now()]);
            if ($updated === 1) {
                $fresh = $current->fresh();
                app(RecordAdministrativeActivity::class)->handle($actor, $sent ? AdministrativeAction::InvitationSent : AdministrativeAction::InvitationSendFailed, $fresh,
                    ['sent_at' => $current->sent_at?->toIso8601String(), 'send_failed_at' => $current->send_failed_at?->toIso8601String()],
                    ['sent_at' => $fresh->sent_at?->toIso8601String(), 'send_failed_at' => $fresh->send_failed_at?->toIso8601String()]);
            }

            return $updated;
        });
    }
}
