<?php

namespace App\Livewire\Setup;

use App\Models\Setting;
use App\Notifications\SetupTestEmailNotification;
use Flux\Flux;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Title('Setup checklist')]
class Index extends Component
{
    /**
     * Live diagnostic state for every checklist step.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $steps = [];

    /**
     * Mount the component and run every live diagnostic.
     */
    public function mount(): void
    {
        $this->refreshDiagnostics();
    }

    /**
     * Send a test email and persist only its explicit result.
     */
    public function sendTestEmail(): void
    {
        $this->resetErrorBag('test_email');

        $recipient = $this->effectiveRecipient();
        $key = 'setup-test-email:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $message = __('Too many test emails. Please try again later.');
            $this->addError('test_email', $message);
            Flux::toast(variant: 'danger', text: $message);

            return;
        }

        RateLimiter::hit($key, 3600);

        try {
            Notification::route('mail', $recipient)->notify(
                (new SetupTestEmailNotification(app()->getLocale()))->locale(app()->getLocale()),
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->persistEmailResult(false, $recipient);
            $this->refreshDiagnostics();
            Flux::toast(variant: 'danger', text: __('The test email could not be sent. Check the application logs for details.'));

            return;
        }

        $this->persistEmailResult(true, $recipient);
        $this->refreshDiagnostics();
        Flux::toast(variant: 'success', text: __('The mail transport accepted the test message. Delivery to the inbox is not guaranteed.'));
    }

    /**
     * Re-run the storage diagnostic on demand.
     */
    public function recheckStorage(): void
    {
        $this->steps['storage'] = $this->diagnoseStorage();
        Flux::toast(variant: 'success', text: __('Storage status refreshed.'));
    }

    /**
     * Recompute the state of every checklist step from the real system state.
     */
    protected function refreshDiagnostics(): void
    {
        $this->steps = [
            'identity' => $this->diagnoseIdentity(),
            'email' => $this->diagnoseEmail(),
            'storage' => $this->diagnoseStorage(),
            'registration' => $this->diagnoseRegistration(),
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
    protected function diagnoseEmail(): array
    {
        $settings = Setting::current();
        $recipient = $this->effectiveRecipient();
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
    protected function diagnoseStorage(): array
    {
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
     * Determine the recipient for the test email.
     */
    protected function effectiveRecipient(): string
    {
        $configured = trim((string) Setting::current()->admin_email);

        if ($configured !== '') {
            return $configured;
        }

        return (string) auth()->user()?->email;
    }

    /**
     * Persist the explicit result of a test email attempt.
     */
    protected function persistEmailResult(bool $sent, string $recipient): void
    {
        $settings = Setting::query()->first() ?? new Setting;

        $settings->fill($sent
            ? ['test_email_sent_at' => now(), 'test_email_sent_to' => $recipient, 'test_email_failed_at' => null]
            : ['test_email_failed_at' => now()]);

        $settings->save();
        Setting::forgetCache();
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
        $disk = Storage::disk('public');
        $name = '.setup-check-'.Str::random(16).'.txt';
        $contents = Str::random(32);

        try {
            if (! $disk->put($name, $contents)) {
                return false;
            }

            if ($disk->get($name) !== $contents) {
                return false;
            }
        } catch (Throwable) {
            return false;
        } finally {
            $disk->delete($name);
        }

        return true;
    }

    public function render(): View
    {
        return view('livewire.setup.index')->layout('layouts.app', [
            'breadcrumbs' => [
                ['label' => __('Setup checklist')],
            ],
        ]);
    }
}
