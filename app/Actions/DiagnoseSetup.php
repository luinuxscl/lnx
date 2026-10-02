<?php

namespace App\Actions;

use App\Ai\AiConfig;
use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DiagnoseSetup
{
    /** @return array<string, array<string, mixed>> */
    public function handle(User $user): array
    {
        abort_unless($user->hasRole(Role::Root) && Gate::forUser($user)->allows('viewAny', Setting::class), 403);

        return [
            'identity' => $this->diagnoseIdentity(),
            'email' => $this->diagnoseEmail($user),
            'storage' => $this->diagnoseStorage($user),
            'registration' => $this->diagnoseRegistration(),
            'ai' => $this->diagnoseAi(),
        ];
    }

    /**
     * Diagnose the site identity step.
     *
     * @return array<string, mixed>
     */
    protected function diagnoseIdentity(): array
    {
        $settings = Setting::current();
        $title = trim((string) $settings->site_title);
        $email = trim((string) $settings->admin_email);
        $valid = $title !== '' && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        return [
            'status' => $valid ? 'verified' : 'pending',
            'title' => $settings->site_title,
            'tagline' => $settings->site_tagline,
            'admin_email' => $settings->admin_email,
        ];
    }

    /**
     * Diagnose the test email step from the persisted send result.
     *
     * @return array<string, mixed>
     */
    protected function diagnoseEmail(User $user): array
    {
        $settings = Setting::current();
        $recipient = $this->effectiveRecipient($user);
        $sentAt = $settings->test_email_sent_at;
        $failedAt = $settings->test_email_failed_at;
        $sentTo = $settings->test_email_sent_to;

        if ($failedAt !== null && ($sentAt === null || $failedAt->greaterThan($sentAt))) {
            $status = 'error';
        } elseif ($sentAt !== null && $sentTo === $recipient) {
            $status = 'verified';
        } else {
            $status = 'pending';
        }

        return [
            'status' => $status,
            'recipient' => $recipient,
            'sent_at' => $sentAt?->toIso8601String(),
            'sent_to' => $sentTo,
            'driver' => (string) config('mail.default'),
            'from' => config('mail.from.address'),
        ];
    }

    /**
     * Diagnose the public storage step (link + write/read/delete probe).
     *
     * @return array<string, mixed>
     */
    public function diagnoseStorage(User $user): array
    {
        abort_unless($user->hasRole(Role::Root) && Gate::forUser($user)->allows('viewAny', Setting::class), 403);

        $linked = $this->storageLinkExists();
        $writable = $linked && $this->storageIsWritable();

        return [
            'status' => $linked && $writable ? 'verified' : 'error',
            'linked' => $linked,
            'writable' => $writable,
        ];
    }

    /**
     * Diagnose the registration mode step.
     *
     * @return array<string, mixed>
     */
    protected function diagnoseRegistration(): array
    {
        $settings = Setting::current();
        $configured = Setting::query()->exists();

        return [
            'status' => $configured ? 'verified' : 'pending',
            'configured' => $configured,
            'mode' => $settings->registration_mode ?? 'open',
        ];
    }

    /**
     * Diagnose the AI configuration step.
     *
     * A disabled integration is pending, not an error: a fresh install has no
     * AI credentials. Once enabled, missing credentials or an unlisted model
     * are errors until configured.
     *
     * @return array<string, mixed>
     */
    protected function diagnoseAi(): array
    {
        $config = app(AiConfig::class);
        $issues = $config->issues();
        $ready = $config->isReady();

        $status = match (true) {
            $ready => 'verified',
            ! $config->enabled() => 'pending',
            in_array('missing_api_key', $issues, true),
            in_array('provider_not_configured', $issues, true),
            in_array('missing_model', $issues, true) => 'pending',
            default => 'error',
        };

        return [
            'status' => $status,
            'ready' => $ready,
            'enabled' => $config->enabled(),
            'provider' => $config->providerName(),
            'model' => $config->defaultModel(),
            'api_key_set' => $config->apiKeyPresent(),
            'issues' => $issues,
        ];
    }

    /**
     * Determine the recipient for the test email.
     */
    public function effectiveRecipient(User $user): string
    {
        abort_unless($user->hasRole(Role::Root) && Gate::forUser($user)->allows('viewAny', Setting::class), 403);

        $configured = trim((string) Setting::current()->admin_email);

        if ($configured !== '') {
            return $configured;
        }

        return $user->email;
    }

    /**
     * Determine whether the public storage link exists.
     */
    protected function storageLinkExists(): bool
    {
        $path = public_path('storage');

        return is_link($path) || file_exists($path);
    }

    /**
     * Probe the public disk by writing, reading back and deleting a file.
     */
    protected function storageIsWritable(): bool
    {
        $name = '.setup-check-'.Str::random(16).'.txt';
        $contents = Str::random(32);

        try {
            $disk = Storage::disk('public');
        } catch (Throwable) {
            return false;
        }

        try {
            $verified = $disk->put($name, $contents) && $disk->get($name) === $contents;
        } catch (Throwable) {
            $verified = false;
        }

        try {
            return $disk->delete($name) && $verified;
        } catch (Throwable) {
            return false;
        }
    }
}
