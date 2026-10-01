<?php

namespace App\Actions;

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

                return UserInvitation::create([
                    'email' => $email,
                    'pending_email' => $email,
                    'role' => $role,
                    'locale' => $locale,
                    'invited_by' => $actor->id,
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => now()->addDays(7),
                ]);
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (UserInvitation::query()->where('pending_email', $email)->exists()) {
                throw ValidationException::withMessages(['email' => __('A pending invitation exists. Resend it from the invitation list.')]);
            }

            throw $exception;
        }

        return $this->send($invitation, $token);
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
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => __('A pending invitation exists. Resend it from the invitation list.')]);
        }

        if ($updated !== 1) {
            throw ValidationException::withMessages(['invitation' => __('This invitation is no longer available.')]);
        }

        return $this->send($invitation->refresh(), $token);
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

    private function send(UserInvitation $invitation, string $token): bool
    {
        try {
            Notification::route('mail', $invitation->email)->notify(new UserInvitationNotification(
                route('invitations.accept', ['invitation' => $invitation->id, 'token' => $token]),
                $invitation->locale,
            )->locale($invitation->locale));
        } catch (Throwable $exception) {
            report($exception);
            UserInvitation::query()->whereKey($invitation->id)->whereNull('accepted_at')->whereNull('revoked_at')
                ->where('token_hash', hash('sha256', $token))
                ->update(['send_failed_at' => now()]);

            return false;
        }

        $updated = UserInvitation::query()->whereKey($invitation->id)
            ->whereNull('accepted_at')->whereNull('revoked_at')
            ->where('token_hash', hash('sha256', $token))
            ->update(['sent_at' => now(), 'send_failed_at' => null]);

        return $updated === 1;
    }
}
