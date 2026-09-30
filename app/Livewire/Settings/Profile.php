<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Profile settings')]
class Profile extends Component
{
    use ProfileValidationRules, WithFileUploads;

    public string $name = '';

    public string $email = '';

    public mixed $avatar = null;

    public function saveAvatar(): void
    {
        $this->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();
        $previousPath = $user->avatar_path;
        $newPath = $this->avatar->store("avatars/{$user->id}", 'public');

        if ($newPath === false) {
            throw new \RuntimeException('Unable to store the avatar.');
        }

        try {
            $user->avatar_path = $newPath;
            $user->save();
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPath);

            throw $exception;
        }

        if ($previousPath !== null) {
            Storage::disk('public')->delete($previousPath);
        }

        $this->reset('avatar');

        Flux::toast(variant: 'success', text: __('Avatar updated.'));

        $this->redirect(route('profile.edit'), navigate: true);
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();
        $previousPath = $user->avatar_path;

        if ($previousPath === null) {
            return;
        }

        $user->avatar_path = null;
        $user->save();

        Storage::disk('public')->delete($previousPath);

        $this->reset('avatar');

        Flux::toast(variant: 'success', text: __('Avatar removed.'));

        $this->redirect(route('profile.edit'), navigate: true);
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));

        $this->redirect(route('profile.edit'), navigate: true);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: __('A new verification link has been sent to your email address.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        $user = Auth::user();

        return $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        $user = Auth::user();

        return ! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail();
    }

    public function render(): View
    {
        return view('livewire.settings.profile')
            ->layout('layouts.app', [
                'breadcrumbs' => [
                    ['label' => __('Settings'), 'href' => route('profile.edit')],
                    ['label' => __('Profile')],
                ],
            ]);
    }
}
