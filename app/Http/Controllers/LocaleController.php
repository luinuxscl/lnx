<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Update the locale stored in the session and, when signed in, on the account.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('app.available_locales')))],
        ]);

        $request->session()->put('locale', $validated['locale']);

        $user = $request->user();

        if ($user instanceof User) {
            $user->update(['locale' => $validated['locale']]);
        }

        return back();
    }
}
