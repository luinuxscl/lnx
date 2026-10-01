<?php

namespace App\Http\Controllers;

use App\Actions\AcceptUserInvitation;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AcceptInvitationController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    public function create(Request $request, UserInvitation $invitation, string $token): View
    {
        return view('livewire.auth.accept-invitation', [
            'invitation' => $invitation,
            'token' => $token,
            'available' => ! Auth::check() && $invitation->isPending()
                && hash_equals($invitation->token_hash, hash('sha256', $token)),
        ]);
    }

    public function store(Request $request, UserInvitation $invitation, string $token, AcceptUserInvitation $accept): RedirectResponse
    {
        if (Auth::check()) {
            return back()->withErrors(['invitation' => __('Sign out before accepting an invitation.')]);
        }

        $validated = $request->validate([
            'name' => $this->nameRules(),
            'password' => $this->passwordRules(),
        ]);

        $user = $accept->handle($invitation, $token, $validated['name'], $validated['password']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
